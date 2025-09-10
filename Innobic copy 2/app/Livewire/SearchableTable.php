<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class SearchableTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortDirection = 'asc';
    
    // Properties สำหรับกำหนด data และ config
    public $modelClass;
    public $columnConfig = [];
    public $searchableFields = [];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount($modelClass = null, $columnConfig = [], $searchableFields = [])
    {
        $this->modelClass = $modelClass;
        $this->columnConfig = $columnConfig;
        $this->searchableFields = $searchableFields;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function getDataProperty()
    {
        if (!$this->modelClass) {
            return collect();
        }

        $query = app($this->modelClass)->query();

        // Apply search
        if ($this->search && !empty($this->searchableFields)) {
            $query->where(function($q) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', '%' . $this->search . '%');
                }
            });
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    // Format value based on column configuration
    public function formatValue($item, $column)
    {
        $value = data_get($item, $column['field']);
        
        // Handle different field types
        switch ($column['type'] ?? 'text') {
            case 'date':
                return $value ? $value->format($column['format'] ?? 'd/m/Y') : '-';
            case 'datetime':
                return $value ? $value->format($column['format'] ?? 'd/m/Y H:i') : '-';
            case 'badge':
                $badgeClass = $column['badge_class'] ?? 'bg-primary';
                return "<span class='badge {$badgeClass}'>{$value}</span>";
            case 'link':
                $url = $column['url'] ?? '#';
                return "<a href='{$url}' class='text-blue-600 hover:text-blue-800'>{$value}</a>";
            default:
                return $value;
        }
    }

    public function render()
    {
        return view('livewire.searchable-table', [
            'data' => $this->data,
            'columns' => $this->columnConfig
        ]);
    }
}
