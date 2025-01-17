<?php

namespace Kunstmaan\AdminListBundle\AdminList;

use Kunstmaan\AdminListBundle\AdminList\FilterType\FilterTypeInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

class FilterBuilder
{
    /**
     * @var array
     */
    private $filterDefinitions = [];

    /**
     * @var Filter[]
     */
    private $currentFilters = [];

    /**
     * @var array
     */
    private $currentParameters = [];

    /**
     * @param string                   $columnName The column name
     * @param FilterTypeInterface|null $type       The filter type
     * @param string|null              $filterName The name of the filter
     * @param array                    $options    Options
     *
     * @return FilterBuilder
     */
    public function add($columnName, FilterTypeInterface $type = null, $filterName = null, array $options = [])
    {
        $this->filterDefinitions[$columnName] = [
            'type' => $type,
            'options' => $options,
            'filtername' => $filterName,
        ];

        return $this;
    }

    /**
     * @param string $columnName
     *
     * @return mixed|null
     */
    public function get($columnName)
    {
        if (isset($this->filterDefinitions[$columnName])) {
            return $this->filterDefinitions[$columnName];
        }

        return null;
    }

    /**
     * @param string $columnName
     *
     * @return FilterBuilder
     */
    public function remove($columnName)
    {
        if (isset($this->filterDefinitions[$columnName])) {
            unset($this->filterDefinitions[$columnName]);
        }

        return $this;
    }

    /**
     * @param string $columnName
     *
     * @return bool
     */
    public function has($columnName)
    {
        return isset($this->filterDefinitions[$columnName]);
    }

    /**
     * @return array
     */
    public function getFilterDefinitions()
    {
        return $this->filterDefinitions;
    }

    public function bindRequest(Request $request)
    {
        $filterBuilderName = 'filter_' . $request->attributes->get('_route');

        $this->currentParameters = $request->query->all();
        if (\count($this->currentParameters) === 0) {
            if (!$request->query->has('filter') && $request->getSession()->has($filterBuilderName)) {
                $savedQuery = $request->getSession()->get($filterBuilderName);
                $request->query->replace($savedQuery);
                $this->currentParameters = $savedQuery;
            }
        } else {
            $request->getSession()->set($filterBuilderName, $this->currentParameters);
        }

        $filterColumnNames = class_exists(InputBag::class) ? $request->query->all('filter_columnname') : $request->query->get('filter_columnname', []);
        $uniqueIds = class_exists(InputBag::class) ? $request->query->all('filter_uniquefilterid') : $request->query->get('filter_uniquefilterid', []);
        $index = 0;
        foreach ($filterColumnNames as $filterColumnName) {
            $uniqueId = $uniqueIds[$index];
            $filter = new Filter($filterColumnName, $this->get($filterColumnName), $uniqueId);
            $this->currentFilters[] = $filter;
            $filter->bindRequest($request);
            ++$index;
        }
    }

    /**
     * @return array
     */
    public function getCurrentParameters()
    {
        return $this->currentParameters;
    }

    /**
     * @return Filter[]
     */
    public function getCurrentFilters()
    {
        return $this->currentFilters;
    }
}
