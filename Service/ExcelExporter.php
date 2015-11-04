<?php
/**
 * Date: 07.07.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Service;


use Doctrine\ORM\QueryBuilder;
use ExcelAnt\Adapter\PhpExcel\Writer\PhpExcelWriter\Excel5;
use ExcelAnt\Adapter\PhpExcel\Writer\WriterFactory;
use ExcelAnt\Table\Label;
use Symfony\Component\DependencyInjection\ContainerAware,
    ExcelAnt\Adapter\PhpExcel\Workbook\Workbook,
    ExcelAnt\Adapter\PhpExcel\Sheet\Sheet,
    ExcelAnt\Adapter\PhpExcel\Writer\Writer,
    ExcelAnt\Table\Table,
    ExcelAnt\Coordinate\Coordinate;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ExcelExporter extends ContainerAware
{
    const PAGE_LIMIT = 100;

    /**
     * @var PropertyAccessor
     */
    private $accessor;

    public function __construct()
    {
        if(!class_exists('ExcelAnt\Adapter\PhpExcel\Workbook\Workbook')) {
            throw new \Exception('You must include "wisembly/excelant" to your composer to use export');
        }

        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function export($moduleConfig)
    {
        $workbook = new Workbook();
        $sheet = new Sheet($workbook);
        $table = new Table();
        $label = new Label();

        $query = $this->createQuery($moduleConfig);

        $columnsConfig = $this->getColumnConfig($moduleConfig);

        $label->setValues(array_map(function($el){
            return $el['title'];
        }, $columnsConfig));

        $page = 0;
        $continue = true;

        while($continue){
            $query->setFirstResult($page * self::PAGE_LIMIT)
                ->setMaxResults(self::PAGE_LIMIT);

            $items = $query->getQuery()->getResult();

            foreach($items as $item){
                if($row = $this->createTableRow($columnsConfig, $item)){
                    $table->setRow($row);
                }
            }

            if(!$items){
                $continue = false;
            }

            $page++;
        }

        $table->setLabel($label);
        $sheet->addTable($table, new Coordinate(1, 1));
        $workbook->addSheet($sheet);

        $path = $this->generatePath();

        $writer = (new WriterFactory())->createWriter(new Excel5($path));

        $phpExcel = $writer->convert($workbook);
        $writer->write($phpExcel);

        return $path;
    }

    private function generatePath()
    {
        return sprintf('%s%s.%s', sys_get_temp_dir(), uniqid(), 'xls');
    }

    private function getColumnConfig($moduleConfig)
    {
        if(array_key_exists('show', $moduleConfig['actions']['export']) && is_array($moduleConfig['actions']['export']['show'])){
            $columns = [];

            foreach($moduleConfig['actions']['export']['show'] as $field){
                if(array_key_exists($field, $moduleConfig['columns'])){
                    $columns[$field] = $moduleConfig['columns'][$field];
                }else{
                    throw new \Exception(sprintf('Can\'t find config for field %s', $field));
                }
            }

            return $columns;
        }

        return $moduleConfig['actions']['columns'];
    }

    private function createQuery($moduleConfig)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->container->get('doctrine')->getManager()->createQueryBuilder();
        $queryBuilder->select('t')->from($moduleConfig['entity'], 't');

        if (!empty($moduleConfig['sort'])) {
            $queryBuilder->orderBy('t.' . $moduleConfig['sort'][0], $moduleConfig['sort'][1]);
        }

        return $queryBuilder;
    }

    private function createTableRow($columnsConfig, $item)
    {
        $result = [];
        foreach($columnsConfig as $columnName => $column){
            $value = $this->accessor->getValue($item, $columnName);
            $result[] = $this->prepareValue($column, $value);
        }

        return $result;
    }

    private function prepareValue($columnConfig, $value)
    {
        switch(true){
            case $columnConfig['type'] == 'date':
                return $value->format(!empty($columnConfig['format']) ? $columnConfig['format'] : 'd.m.Y H:i:s');

            case $value instanceof \IteratorAggregate:
                $parts = [];
                foreach($value as $valueItem){
                    $parts[] = $valueItem->__toString();
                }
                
                return implode(', ', $parts);

            case is_object($value):
                return $value->__toString();

            case $columnConfig['type'] == 'boolean':
                return $value ? 'yes' : 'false';

            default:
                return $value;
        }
    }
}
