<?php
/**
 * Date: 21.07.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Command;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class AdminGenerateCommand extends ContainerAwareCommand
{

    private $defaultConfigPath = '/../Resources/config/config.default.yml';

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $entity = $input->getArgument('entity');
        /** @var ClassMetadata $metadata */
        $metadata = $this->getContainer()->get('doctrine')->getManager()->getClassMetadata($entity);

        if($metadata){
            $question = new Question('Please enter admin controller [dictionary]: ', 'dictionary');
            $controller = $helper->ask($input, $output, $question);

            $key = str_replace('\\', '_', Inflector::tableize($metadata->getName()));
            $question = new Question(sprintf('Please enter admin module key [%s]: ', $key), $key);
            $key = $helper->ask($input, $output, $question);

            if($controller && $key){
                $content = $this->getConfigFileContent($metadata, $controller, $key);

                $targetPath = $this->getContainer()->getParameter('kernel.root_dir')
                    .sprintf('/config/admin/structure.%s.yml', $key);

                if(file_put_contents($targetPath, $content)){
                    $output->writeln(sprintf('<fg=green>File was saved to \'%s\'</fg=green>', $targetPath));
                }else{
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
        $columns = [];

        foreach($metadata->getFieldNames() as $fieldName){
            $columns[] = $fieldName;

            if(!($metadata->isIdentifier($fieldName) && $metadata->idGenerator->isPostInsertGenerator())){
                $listShowFields[] = $fieldName;
            }
        }

        $parts = explode('\\', $metadata->getName());
        $title = Inflector::pluralize($parts[count($parts) - 1]);

        return $this->generateConfig($key, $title, $controller, $metadata->getName(), $columns, $listShowFields);
    }


    private function generateConfig($key, $title, $controller, $entity, $columns, $listShowFields)
    {
        $configContent = file_get_contents(dirname('/Users/vasilportey/projects/jobrain-site/vendor/youshido/admin/Command/AdminGenerateCommand.php').$this->defaultConfigPath);

        return str_replace([
            '{key}',
            '{title}',
            '{controller}',
            '{entity}',
            '{columns}',
            '{show}',
        ], [
            $key,
            $title,
            $controller,
            $entity,
            implode("\n", array_map(function($el){ return  sprintf('        %s: ~', $el); }, (array) $columns)),
            implode(', ', $listShowFields)
        ],$configContent);
    }

}