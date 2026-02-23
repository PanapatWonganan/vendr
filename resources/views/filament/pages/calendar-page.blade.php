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

            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded" style="background-color: #8b5cf6;"></span>
                    <span class="text-sm text-gray-800 dark:text-gray-200">GR ที่ผ่านมา</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded" style="background-color: #a855f7;"></span>
                    <span class="text-sm text-gray-800 dark:text-gray-200">GR ล่าสุด (≤3 วัน)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded" style="background-color: #c084fc;"></span>
                    <span class="text-sm text-gray-800 dark:text-gray-200">GR อนาคต (>3 วัน)</span>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-start space-x-2 text-xs text-gray-700 dark:text-gray-300">
                    <x-heroicon-o-light-bulb class="w-4 h-4 flex-shrink-0 mt-0.5 text-yellow-500 dark:text-yellow-400" />
                    <div>
                        <strong class="text-gray-900 dark:text-gray-100">เคล็ดลับ:</strong>
                        <span class="text-gray-700 dark:text-gray-300">คลิกที่เหตุการณ์เพื่อดูรายละเอียด •
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

            /* GR Priority colors */
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
                    selectable: false,
                    editable: false,
                    
                    // Event handlers
                    eventClick: function(info) {
                        const event = info.event;
                        const entityId = event.extendedProps.entity_id;
                        const description = event.extendedProps.description;
                        const priority = event.extendedProps.priority;

                        const priorityText = {
                            'gr_past': 'ที่ผ่านมา',
                            'gr_recent': 'ล่าสุด',
                            'gr_future': 'อนาคต'
                        };

                        if (confirm(`Goods Receipt: ${event.title}\n\nรายละเอียด: ${description}\nสถานะ: ${priorityText[priority] || priority}\n\nคลิก OK เพื่อดูรายละเอียด`)) {
                            window.location.href = `/admin/goods-receipts/${entityId}/edit`;
                        }

                        info.jsEvent.preventDefault();
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