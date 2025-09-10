<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            ปฏิทินกำหนดการส่งมอบงาน
        </x-slot>

        <div class="p-4">
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
                <strong>Debug Info:</strong> 
                <span id="debug-info">Starting calendar initialization...</span>
            </div>
            
            @php
                $events = $this->getCalendarEvents();
            @endphp
            
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded">
                <strong>PHP Debug:</strong> 
                Found {{ count($events) }} events in PHP
                @if(count($events) > 0)
                    <br>First event: {{ $events[0]['title'] ?? 'No title' }}
                @endif
            </div>
            
            <div id="calendar-widget" style="min-height: 600px; border: 1px solid #ccc; background: #f9f9f9;"></div>
        </div>
        
        <script>
            // First, let's test basic functionality
            document.addEventListener('DOMContentLoaded', function() {
                console.log('Calendar script starting...');
                var debugInfo = document.getElementById('debug-info');
                var calendarEl = document.getElementById('calendar-widget');
                
                if (!debugInfo) {
                    console.error('Debug info element not found');
                    return;
                }
                
                if (!calendarEl) {
                    debugInfo.textContent = 'ERROR: Calendar element not found';
                    return;
                }
                
                debugInfo.textContent = 'Elements found, checking data...';
                
                try {
                    var events = @json($events);
                    debugInfo.textContent = 'Found ' + events.length + ' events from PHP. Testing simple display...';
                    
                    // Create simple event list first to test
                    var eventList = document.createElement('div');
                    eventList.className = 'mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded';
                    eventList.innerHTML = '<strong>Events List:</strong><ul>';
                    
                    for (var i = 0; i < Math.min(3, events.length); i++) {
                        eventList.innerHTML += '<li>' + events[i].title + ' (' + events[i].start + ')</li>';
                    }
                    eventList.innerHTML += '</ul>';
                    
                    calendarEl.parentNode.insertBefore(eventList, calendarEl);
                    
                    debugInfo.textContent = 'Events displayed. Now loading FullCalendar...';
                    
                    // Simple timeout to test
                    setTimeout(function() {
                        debugInfo.textContent = 'Timeout test passed. Calendar should work now.';
                    }, 1000);
                    
                } catch (error) {
                    debugInfo.textContent = 'ERROR: ' + error.message;
                    console.error('Calendar error:', error);
                }
            });
            
            function initCalendar(events, calendarEl, debugInfo) {
                try {
                    if (!window.FullCalendar) {
                        debugInfo.textContent = 'ERROR: FullCalendar still not available';
                        return;
                    }
                
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 'auto',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listWeek'
                    },
                    events: events,
                    locale: 'th', // Set Thai locale
                    editable: true, // Enable drag & drop
                    eventClick: function(info) {
                        if (info.event.url) {
                            window.location.href = info.event.url;
                        }
                    },
                    eventDrop: function(info) {
                        // Handle drag & drop
                        if (!info.event.extendedProps.editable) {
                            info.revert();
                            return;
                        }

                        var entityId = info.event.extendedProps.entity_id;
                        var entityType = info.event.extendedProps.entity_type;
                        var newDate = info.event.start.toISOString().split('T')[0];

                        // Send AJAX request to update date
                        var csrfToken = document.querySelector('meta[name="csrf-token"]');
                        var headers = {
                            'Content-Type': 'application/json'
                        };
                        if (csrfToken) {
                            headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
                        }
                        
                        fetch('/dashboard/update-event-date', {
                            method: 'POST',
                            headers: headers,
                            body: JSON.stringify({
                                id: entityId,
                                type: entityType,
                                new_date: newDate
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                info.revert();
                                alert('เกิดข้อผิดพลาด: ' + data.message);
                            } else {
                                // Show success message (you can customize this)
                                console.log('อัปเดตวันที่สำเร็จ: ' + data.message);
                            }
                        })
                        .catch(error => {
                            info.revert();
                            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                        });
                    },
                    eventDidMount: function(info) {
                        // Add tooltip
                        info.el.title = info.event.extendedProps.description || info.event.title;
                        
                        // Add cursor pointer for editable events
                        if (info.event.extendedProps.editable) {
                            info.el.style.cursor = 'move';
                        }
                    }
                });
                
                calendar.render();
                
                // Add error handling
                calendar.on('eventRenderFailure', function(info) {
                    console.error('Event render failure:', info);
                });
                
                    debugInfo.textContent = 'Calendar rendered successfully with ' + events.length + ' events';
                    console.log('Calendar rendered successfully');
                    
                } catch (error) {
                    debugInfo.textContent = 'ERROR in initCalendar: ' + error.message;
                    console.error('Calendar init error:', error);
                }
            }
        </script>
    </x-filament::section>
</x-filament-widgets::widget>