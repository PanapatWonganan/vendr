<x-filament-panels::page>
    <div class="space-y-4">
        <!-- Calendar Legend -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    <x-heroicon-o-information-circle class="w-5 h-5 inline mr-2" />
                    คำอธิบายการแสดงผล
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Purchase Orders -->
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">📋</span>
                        <div>
                            <strong class="text-blue-700 dark:text-blue-300">Purchase Orders (PO)</strong>
                            <p class="text-xs text-gray-500 dark:text-gray-400">วันกำหนดส่งมอบสินค้า</p>
                        </div>
                    </div>
                    <ul class="ml-8 space-y-1 text-sm">
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 bg-red-500 rounded mr-2"></span>
                            <span class="text-gray-800 dark:text-gray-200">เกินกำหนด</span>
                        </li>
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 bg-orange-500 rounded mr-2"></span>
                            <span class="text-gray-800 dark:text-gray-200">เร่งด่วน (≤3 วัน)</span>
                        </li>
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 bg-yellow-500 rounded mr-2"></span>
                            <span class="text-gray-800 dark:text-gray-200">สำคัญ (4-7 วัน)</span>
                        </li>
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 bg-blue-500 rounded mr-2"></span>
                            <span class="text-gray-800 dark:text-gray-200">ปกติ (>7 วัน)</span>
                        </li>
                    </ul>
                </div>

                <!-- Purchase Requisitions -->
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">📝</span>
                        <div>
                            <strong class="text-green-700 dark:text-green-300">Purchase Requisitions (PR)</strong>
                            <p class="text-xs text-gray-500 dark:text-gray-400">วันที่ต้องการใช้งาน</p>
                        </div>
                    </div>
                    <ul class="ml-8 space-y-1 text-sm">
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 bg-red-600 rounded mr-2"></span>
                            <span class="text-gray-800 dark:text-gray-200">เกินกำหนด</span>
                        </li>
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 bg-orange-600 rounded mr-2"></span>
                            <span class="text-gray-800 dark:text-gray-200">เร่งด่วน (≤3 วัน)</span>
                        </li>
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 bg-yellow-600 rounded mr-2"></span>
                            <span class="text-gray-800 dark:text-gray-200">สำคัญ (4-7 วัน)</span>
                        </li>
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 bg-green-600 rounded mr-2"></span>
                            <span class="text-gray-800 dark:text-gray-200">ปกติ (>7 วัน)</span>
                        </li>
                    </ul>
                </div>

                <!-- Goods Receipts -->
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl">📦</span>
                        <div>
                            <strong class="text-purple-700 dark:text-purple-300">Goods Receipts (GR)</strong>
                            <p class="text-xs text-gray-500 dark:text-gray-400">วันที่ตรวจรับสินค้า</p>
                        </div>
                    </div>
                    <ul class="ml-8 space-y-1 text-sm">
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 rounded mr-2" style="background-color: #8b5cf6;"></span>
                            <span class="text-gray-800 dark:text-gray-200">ที่ผ่านมา</span>
                        </li>
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 rounded mr-2" style="background-color: #a855f7;"></span>
                            <span class="text-gray-800 dark:text-gray-200">ล่าสุด (≤3 วัน)</span>
                        </li>
                        <li class="flex items-center">
                            <span class="inline-block w-3 h-3 rounded mr-2" style="background-color: #c084fc;"></span>
                            <span class="text-gray-800 dark:text-gray-200">อนาคต (>3 วัน)</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-start space-x-2 text-xs text-gray-700 dark:text-gray-300">
                    <x-heroicon-o-light-bulb class="w-4 h-4 flex-shrink-0 mt-0.5 text-yellow-500 dark:text-yellow-400" />
                    <div>
                        <strong class="text-gray-900 dark:text-gray-100">เคล็ดลับ:</strong>
                        <span class="text-gray-700 dark:text-gray-300">คลิกที่เหตุการณ์เพื่อดูรายละเอียด •
                        สีแดง/ส้มต้องติดตามด่วน •
                        ข้อมูลแสดงเฉพาะบริษัทที่เลือก</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Container -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
            <div id="calendar"></div>
        </div>
    </div>

    @push('styles')
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
        <style>
            /* Calendar Custom Styling */
            #calendar {
                max-width: 100%;
                margin: 0 auto;
            }

            .fc-theme-standard .fc-scrollgrid {
                border: 1px solid #e5e7eb;
            }

            .fc-theme-standard td,
            .fc-theme-standard th {
                border-color: #e5e7eb;
            }

            .fc-event {
                cursor: pointer;
                border-radius: 4px;
                padding: 2px 4px;
                font-size: 12px;
            }

            .fc-event:hover {
                opacity: 0.8;
                transform: scale(1.02);
            }

            /* Priority colors */
            .fc-event[data-priority="po_overdue"] {
                background-color: #ef4444 !important;
                border-color: #dc2626 !important;
            }

            .fc-event[data-priority="po_urgent"] {
                background-color: #f97316 !important;
                border-color: #ea580c !important;
            }

            .fc-event[data-priority="po_high"] {
                background-color: #eab308 !important;
                border-color: #ca8a04 !important;
            }

            .fc-event[data-priority="po_normal"] {
                background-color: #3b82f6 !important;
                border-color: #2563eb !important;
            }

            .fc-event[data-priority="pr_overdue"] {
                background-color: #dc2626 !important;
                border-color: #b91c1c !important;
            }

            .fc-event[data-priority="pr_urgent"] {
                background-color: #ea580c !important;
                border-color: #c2410c !important;
            }

            .fc-event[data-priority="pr_high"] {
                background-color: #ca8a04 !important;
                border-color: #a16207 !important;
            }

            .fc-event[data-priority="pr_normal"] {
                background-color: #059669 !important;
                border-color: #047857 !important;
            }

            .fc-event[data-priority="gr_past"] {
                background-color: #8b5cf6 !important;
                border-color: #7c3aed !important;
            }

            .fc-event[data-priority="gr_recent"] {
                background-color: #a855f7 !important;
                border-color: #9333ea !important;
            }

            .fc-event[data-priority="gr_future"] {
                background-color: #c084fc !important;
                border-color: #a855f7 !important;
            }

            /* Dark mode */
            .dark .fc-theme-standard .fc-scrollgrid {
                border-color: #374151;
            }

            .dark .fc-theme-standard td,
            .dark .fc-theme-standard th {
                border-color: #374151;
            }

            .dark .fc-col-header {
                background-color: #374151;
            }

            .dark .fc-daygrid-day {
                background-color: #1f2937;
            }

            .dark .fc-toolbar {
                color: #f9fafb;
            }
        </style>
    @endpush

    @push('scripts')
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const calendarEl = document.getElementById('calendar');
                const calendarEvents = @json($calendarEvents->values());

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                    },
                    initialView: 'dayGridMonth',
                    locale: 'th',
                    firstDay: 1, // Monday
                    height: 'auto',
                    
                    // Events
                    events: calendarEvents,
                    eventDisplay: 'block',
                    dayMaxEvents: 3,
                    moreLinkClick: 'popover',
                    
                    // Interaction
                    selectable: true,
                    editable: true,
                    
                    // Event handlers
                    eventClick: function(info) {
                        const event = info.event;
                        const entityId = event.extendedProps.entity_id;
                        const entityType = event.extendedProps.entity_type;
                        const description = event.extendedProps.description;
                        const priority = event.extendedProps.priority;
                        
                        // Show event details
                        const priorityText = {
                            'po_overdue': 'เกินกำหนด',
                            'po_urgent': 'ด่วนมาก',
                            'po_high': 'ด่วน',
                            'po_normal': 'ปกติ',
                            'pr_overdue': 'เกินกำหนด',
                            'pr_urgent': 'ด่วนมาก',
                            'pr_high': 'ด่วน',
                            'pr_normal': 'ปกติ',
                            'gr_past': 'ที่ผ่านมา',
                            'gr_recent': 'ล่าสุด',
                            'gr_future': 'อนาคต'
                        };

                        const typeText = entityType === 'po' ? 'Purchase Order' :
                                        entityType === 'pr' ? 'Purchase Requisition' :
                                        'Goods Receipt';
                        
                        if (confirm(`${typeText}: ${event.title}\n\nรายละเอียด: ${description}\nความสำคัญ: ${priorityText[priority] || priority}\n\nคลิก OK เพื่อดูรายละเอียด`)) {
                            // Navigate to the record
                            if (entityType === 'po') {
                                window.location.href = `/admin/purchase-orders/${entityId}/edit`;
                            } else if (entityType === 'pr') {
                                window.location.href = `/admin/purchase-requisitions/${entityId}`;
                            } else if (entityType === 'gr') {
                                window.location.href = `/admin/goods-receipts/${entityId}/edit`;
                            }
                        }
                        
                        info.jsEvent.preventDefault();
                    },
                    
                    eventDrop: function(info) {
                        const event = info.event;
                        const entityId = event.extendedProps.entity_id;
                        const entityType = event.extendedProps.entity_type;
                        const newDate = event.startStr;
                        
                        // Show loading
                        event.setProp('title', event.title + ' (กำลังอัปเดต...)');
                        
                        // Send update request
                        fetch('/dashboard/update-event-date', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                id: entityId,
                                type: entityType,
                                new_date: newDate
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Reset title
                                event.setProp('title', event.title.replace(' (กำลังอัปเดต...)', ''));
                                
                                // Show success notification
                                new FilamentNotification()
                                    .title('อัปเดตสำเร็จ')
                                    .body(data.message)
                                    .success()
                                    .send();
                            } else {
                                throw new Error(data.message || 'เกิดข้อผิดพลาด');
                            }
                        })
                        .catch(error => {
                            // Reset title and revert
                            event.setProp('title', event.title.replace(' (กำลังอัปเดต...)', ''));
                            info.revert();
                            
                            // Show error notification
                            new FilamentNotification()
                                .title('เกิดข้อผิดพลาด')
                                .body(error.message || 'ไม่สามารถอัปเดตได้')
                                .danger()
                                .send();
                        });
                    },
                    
                    eventMouseEnter: function(info) {
                        // Add hover effect
                        info.el.style.transform = 'scale(1.02)';
                        info.el.style.zIndex = '10';
                    },
                    
                    eventMouseLeave: function(info) {
                        // Remove hover effect
                        info.el.style.transform = 'scale(1)';
                        info.el.style.zIndex = 'auto';
                    }
                });

                calendar.render();
                
                // Add priority data attributes to events for CSS styling
                calendar.getEvents().forEach(event => {
                    const eventEl = document.querySelector(`[data-event-id="${event.id}"]`);
                    if (eventEl && event.extendedProps.priority) {
                        eventEl.setAttribute('data-priority', event.extendedProps.priority);
                    }
                });
            });
        </script>
    @endpush
</x-filament-panels::page>