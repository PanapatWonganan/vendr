@props([
    'headers' => [],
    'rows' => [],
    'searchable' => true,
    'sortable' => true,
    'perPage' => 10
])

<div x-data="dataTable({
    headers: @js($headers),
    rows: @js($rows),
    searchable: @js($searchable),
    sortable: @js($sortable),
    perPage: @js($perPage)
})" class="data-table-container rounded-lg shadow overflow-hidden">
    
    <!-- Table Header with Search and Controls -->
    <div x-show="searchable || sortable" class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Search Input -->
            <div x-show="searchable" class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input x-model="search" 
                       type="text" 
                       placeholder="ค้นหา..." 
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full sm:w-64">
            </div>
            
            <!-- Per Page Selector -->
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-700">แสดง:</span>
                <select x-model="perPage" class="border border-gray-300 rounded px-3 py-1 text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-700">รายการ</span>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <template x-for="(header, index) in headers" :key="index">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div x-show="header.sortable !== false && sortable" 
                                 @click="sortBy(header.key)"
                                 class="flex items-center cursor-pointer hover:text-gray-700">
                                <span x-text="header.label"></span>
                                <div class="ml-1">
                                    <i x-show="sortColumn !== header.key" class="fas fa-sort text-gray-300"></i>
                                    <i x-show="sortColumn === header.key && sortDirection === 'asc'" class="fas fa-sort-up text-blue-500"></i>
                                    <i x-show="sortColumn === header.key && sortDirection === 'desc'" class="fas fa-sort-down text-blue-500"></i>
                                </div>
                            </div>
                            <span x-show="header.sortable === false || !sortable" x-text="header.label"></span>
                        </th>
                    </template>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="(row, rowIndex) in paginatedRows" :key="rowIndex">
                    <tr class="hover:bg-gray-50">
                        <template x-for="(header, colIndex) in headers" :key="colIndex">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div x-html="getCellValue(row, header.key, header.render)"></div>
                            </td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- No Results -->
    <div x-show="paginatedRows.length === 0" class="text-center py-12">
        <i class="fas fa-search text-gray-300 text-4xl mb-4"></i>
        <p class="text-gray-500">ไม่พบข้อมูลที่ค้นหา</p>
    </div>

    <!-- Pagination -->
    <div x-show="filteredRows.length > perPage" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                แสดง <span x-text="Math.min((currentPage - 1) * perPage + 1, filteredRows.length)"></span> 
                ถึง <span x-text="Math.min(currentPage * perPage, filteredRows.length)"></span> 
                จาก <span x-text="filteredRows.length"></span> รายการ
            </div>
            
            <div class="flex space-x-1">
                <button @click="goToPage(1)" 
                        :disabled="currentPage === 1"
                        :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-1 text-sm border border-gray-300 rounded">
                    <i class="fas fa-angle-double-left"></i>
                </button>
                
                <button @click="goToPage(currentPage - 1)" 
                        :disabled="currentPage === 1"
                        :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-1 text-sm border border-gray-300 rounded">
                    <i class="fas fa-angle-left"></i>
                </button>
                
                <template x-for="page in visiblePages" :key="page">
                    <button @click="goToPage(page)"
                            :class="page === currentPage ? 'bg-blue-500 text-white' : 'hover:bg-gray-100'"
                            class="px-3 py-1 text-sm border border-gray-300 rounded"
                            x-text="page"></button>
                </template>
                
                <button @click="goToPage(currentPage + 1)" 
                        :disabled="currentPage === totalPages"
                        :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-1 text-sm border border-gray-300 rounded">
                    <i class="fas fa-angle-right"></i>
                </button>
                
                <button @click="goToPage(totalPages)" 
                        :disabled="currentPage === totalPages"
                        :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                        class="px-3 py-1 text-sm border border-gray-300 rounded">
                    <i class="fas fa-angle-double-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function dataTable(config) {
    return {
        // Config
        headers: config.headers,
        originalRows: config.rows,
        searchable: config.searchable,
        sortable: config.sortable,
        
        // State
        search: '',
        sortColumn: '',
        sortDirection: 'asc',
        currentPage: 1,
        perPage: config.perPage,
        
        // Computed
        get filteredRows() {
            let filtered = this.originalRows
            
            if (this.search && this.searchable) {
                const searchTerm = this.search.toLowerCase()
                filtered = filtered.filter(row => {
                    return this.headers.some(header => {
                        const value = this.getNestedValue(row, header.key)
                        return String(value).toLowerCase().includes(searchTerm)
                    })
                })
            }
            
            if (this.sortColumn && this.sortable) {
                filtered.sort((a, b) => {
                    const aVal = this.getNestedValue(a, this.sortColumn)
                    const bVal = this.getNestedValue(b, this.sortColumn)
                    
                    let result = 0
                    if (aVal < bVal) result = -1
                    else if (aVal > bVal) result = 1
                    
                    return this.sortDirection === 'desc' ? -result : result
                })
            }
            
            return filtered
        },
        
        get totalPages() {
            return Math.ceil(this.filteredRows.length / this.perPage)
        },
        
        get paginatedRows() {
            const start = (this.currentPage - 1) * this.perPage
            const end = start + this.perPage
            return this.filteredRows.slice(start, end)
        },
        
        get visiblePages() {
            const pages = []
            const total = this.totalPages
            const current = this.currentPage
            
            let start = Math.max(1, current - 2)
            let end = Math.min(total, current + 2)
            
            if (end - start < 4) {
                if (start === 1) {
                    end = Math.min(total, start + 4)
                } else if (end === total) {
                    start = Math.max(1, end - 4)
                }
            }
            
            for (let i = start; i <= end; i++) {
                pages.push(i)
            }
            
            return pages
        },
        
        // Methods
        sortBy(column) {
            if (!this.sortable) return
            
            if (this.sortColumn === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
            } else {
                this.sortColumn = column
                this.sortDirection = 'asc'
            }
            this.currentPage = 1
        },
        
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page
            }
        },
        
        getNestedValue(obj, path) {
            return path.split('.').reduce((o, p) => o && o[p], obj)
        },
        
        getCellValue(row, key, renderFn) {
            const value = this.getNestedValue(row, key)
            if (renderFn && typeof renderFn === 'function') {
                return renderFn(value, row)
            }
            return value || ''
        },
        
        // Watchers
        init() {
            this.$watch('search', () => {
                this.currentPage = 1
            })
            
            this.$watch('perPage', () => {
                this.currentPage = 1
            })
        }
    }
}
</script> 