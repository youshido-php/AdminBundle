<?php
/**
 * Date: 21.07.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Command;

use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class AdminGenerateCommand extends ContainerAwareCommand
{
    private $tabPrefix = '            ';
    private $defaultConfigPath = 'Resources/config/config.default.yml';
    /** @var  DoctrineOrmTypeGuesser */
    private $guesser;

    protected function configure()
    {
        $this
            ->setName('admin:generate')
            ->setDescription('Generate admin config for entity')
            ->addArgument(
                'entity',
                InputArgument::REQUIRED,
                'For witch entity do you want to generate config?'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->guesser = new DoctrineOrmTypeGuesser($this->getContainer()->get('doctrine'));
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $entity = $input->getArgument('entity');
        /** @var ClassMetadata $metadata */
        $metadata = $this->getContainer()->get('doctrine')->getManager()->getClassMetadata($entity);

        if ($metadata) {
            $question = new Question('Please enter admin controller [dictionary]: ', 'dictionary');
            $controller = $helper->ask($input, $output, $question);

            $key = Inflector::tableize($this->getEntityName($metadata));
            $question = new Question(sprintf('Please enter admin module key [%s]: ', $key), $key);
            $key = $helper->ask($input, $output, $question);

            if ($controller && $key) {
                $content = $this->getConfigFileContent($metadata, $controller, $key);

                $targetPath = $this->getContainer()->getParameter('kernel.root_dir')
                    . sprintf('/config/admin/structure.%s.yml', $key);

                if (file_put_contents($targetPath, $content)) {
                    $output->writeln(sprintf('<fg=green>File was saved to \'%s\'</fg=green>', $targetPath));
                } else {
                    $output->writeln('<error>Can\'t save file (check writes permissions)</error>');
                }
            }
        }
    }

    /**
     * @param $metadata ClassMetadata
     * @param $controller string
     * @param $key string
     * @return string
     */
    private function getConfigFileContent($metadata, $controller, $key)
    {
        $listShowFields = [];
        $actionHideFields = [];
        $columns = [];

        foreach ($metadata->getFieldNames() as $fieldName) {
            $columns[$fieldName] = [
                'type' => $this->recognizeFieldType($metadata, $fieldName)
            ];

            if (!($metadata->isIdentifier($fieldName) && $metadata->idGenerator->isPostInsertGenerator())) {
                $listShowFields[] = $fieldName;
            } else {
                $actionHideFields[] = $fieldName;
            }
        }

        foreach ($metadata->getAssociationMappings() as $field) {
            $columns[$field['fieldName']] = [
                'type' => 'entity',
                'entity' => $field['targetEntity']
            ];
        }

        $title = Inflector::pluralize($this->getEntityName($metadata));

        return $this->generateConfig($key, $title, $controller, $metadata->getName(), $columns, $listShowFields, $actionHideFields);
    }

    /**
     * @param $metadata ClassMetadata
     * @return string
     */
    private function getEntityName($metadata)
    {
        $parts = explode('\\', $metadata->getName());

        return $parts[count($parts) - 1];
    }

    /**
     * @param $metadata ClassMetadata
     * @param $fieldName
     * @return string
     */
    public function recognizeFieldType($metadata, $fieldName)
    {
        $guess = $this->guesser->guessType($metadata->getName(), $fieldName);
        $type = $guess->getType();

        return str_replace(['checkbox'], ['boolean'], $type);

    }

    private function generateConfig($key, $title, $controller, $entity, $columns, $listShowFields, $actionsHideFields)
    {
        $bundlePath = $this->getContainer()->getParameter('kernel.root_dir').'/../vendor/youshido/admin/';
        $configContent = file_get_contents($bundlePath.$this->defaultConfigPath);

        $columnsContent = '';
        foreach($columns as $name => $options){
            $columnsContent .= $this->formatColumn($name, $options);
        }

        $hideContent = '~';
        if($actionsHideFields){
            $hideContent = sprintf("\n%s    hide: [%s]", $this->tabPrefix, implode(', ', $actionsHideFields));
        }

        return str_replace([
            '{key}',
            '{title}',
            '{controller}',
            '{entity}',
            '{columns}',
            '{show}',
            '{hide}'
        ], [
            $key,
            $title,
            $controller,
            $entity,
            $columnsContent,
            implode(', ', $listShowFields),
            $hideContent
        ], $configContent);
    }

    private function formatColumn($name, $options)
    {

        $optionsContent = '';
        foreach($options as $key => $option){
            $optionsContent .= sprintf("\n    %s%s: %s", $this->tabPrefix, $key, $option);
        }

        return sprintf("\n%s%s:%s", $this->tabPrefix, $name, $optionsContent);
    }
}