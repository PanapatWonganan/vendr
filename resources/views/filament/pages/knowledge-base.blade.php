<x-filament-panels::page>
    <div class="fi-page-content">
        <!-- Header Section -->
        <x-filament::section>
            <x-slot name="heading">
                ศูนย์ความรู้ระบบ Innobic
            </x-slot>
            <x-slot name="description">
                รวมคู่มือการใช้งาน เอกสาร และวิดีโอสำหรับทีมงาน เพื่อให้เรียนรู้และใช้งานระบบได้อย่างมีประสิทธิภาพ
            </x-slot>
        </x-filament::section>

        <!-- Featured Videos Section -->
        @if($featuredVideos->count() > 0)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <div class="flex items-center">
                    <x-heroicon-o-play-circle class="w-5 h-5 mr-2 text-danger-500" />
                    วิดีโอแนะนำ
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($featuredVideos as $video)
                <x-filament::card class="overflow-hidden">
                    @if($video->youtube_thumbnail_medium)
                    <div>
                        <div class="aspect-video mb-4 relative group cursor-pointer" 
                             id="video-thumb-{{ $video->id }}"
                             onclick="playVideo('{{ $video->id }}', '{{ $video->youtube_embed_url }}')">
                            <img src="{{ $video->youtube_thumbnail_medium }}" 
                                 alt="{{ $video->title }}"
                                 class="w-full h-full object-cover rounded-lg"
                                 onerror="this.src='https://via.placeholder.com/480x360?text=Video'">
                            
                            <!-- Play button overlay -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="bg-red-600 rounded-full p-3 group-hover:scale-110 transition-transform">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Duration badge -->
                            @if($video->video_duration)
                            <div class="absolute bottom-2 right-2 bg-black bg-opacity-80 text-white text-xs px-1.5 py-0.5 rounded">
                                {{ $video->video_duration }}
                            </div>
                            @endif
                        </div>
                        
                        <!-- Video iframe container (hidden by default) -->
                        <div id="video-player-{{ $video->id }}" class="aspect-video mb-4 hidden">
                            <iframe 
                                id="video-iframe-{{ $video->id }}"
                                src="" 
                                class="w-full h-full rounded-lg"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                            </iframe>
                        </div>
                        
                        <h3 class="font-medium text-gray-950 dark:text-white mb-1">
                            {{ $video->title }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            {{ \App\Models\KnowledgeArticle::CATEGORIES[$video->category] ?? $video->category }}
                        </p>
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>โดย {{ $video->creator->name }}</span>
                            <div class="flex items-center space-x-3">
                                <span class="flex items-center">
                                    <x-heroicon-o-eye class="w-3 h-3 mr-1" />
                                    {{ number_format($video->views_count) }}
                                </span>
                                <a href="{{ route('knowledge.view', $video->id) }}" 
                                   target="_blank" 
                                   class="text-primary-600 dark:text-primary-400 hover:underline">
                                    เปิดหน้าเต็ม
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </x-filament::card>
                @endforeach
            </div>
        </x-filament::section>
        @endif

        <!-- Categories Section -->
        @foreach($categories as $slug => $category)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <div class="flex items-center">
                    @switch($slug)
                        @case('getting-started')
                            <x-heroicon-o-play class="w-5 h-5 mr-2 text-success-500" />
                            @break
                        @case('purchase-requisition')
                            <x-heroicon-o-shopping-cart class="w-5 h-5 mr-2 text-primary-500" />
                            @break
                        @case('purchase-order')
                            <x-heroicon-o-clipboard-document-list class="w-5 h-5 mr-2 text-info-500" />
                            @break
                        @case('goods-receipt')
                            <x-heroicon-o-cube class="w-5 h-5 mr-2 text-warning-500" />
                            @break
                        @case('vendor-management')
                            <x-heroicon-o-building-office class="w-5 h-5 mr-2 text-primary-500" />
                            @break
                        @case('reports')
                            <x-heroicon-o-chart-bar class="w-5 h-5 mr-2 text-danger-500" />
                            @break
                        @case('administration')
                            <x-heroicon-o-cog-6-tooth class="w-5 h-5 mr-2 text-gray-500" />
                            @break
                        @case('troubleshooting')
                            <x-heroicon-o-wrench-screwdriver class="w-5 h-5 mr-2 text-warning-500" />
                            @break
                        @default
                            <x-heroicon-o-document-text class="w-5 h-5 mr-2 text-gray-500" />
                    @endswitch
                    {{ $category['name'] }}
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($category['articles'] as $article)
                <x-filament::card class="hover:shadow-lg transition-shadow overflow-hidden">
                    <div>
                        <!-- Video thumbnail if it's a video -->
                        @if($article->type === 'video' && $article->youtube_thumbnail_medium)
                        <div class="aspect-video mb-3 -mx-4 -mt-4 relative group cursor-pointer"
                             id="video-thumb-cat-{{ $article->id }}"
                             onclick="playVideo('cat-{{ $article->id }}', '{{ $article->youtube_embed_url }}')">
                            <img src="{{ $article->youtube_thumbnail_medium }}" 
                                 alt="{{ $article->title }}"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='https://via.placeholder.com/480x360?text=Video'">
                            
                            <!-- Play button overlay -->
                            <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all">
                                <div class="bg-red-600 rounded-full p-2 group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-white ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Duration badge -->
                            @if($article->video_duration)
                            <div class="absolute bottom-2 right-2 bg-black bg-opacity-80 text-white text-xs px-1.5 py-0.5 rounded">
                                {{ $article->video_duration }}
                            </div>
                            @endif
                        </div>
                        
                        <!-- Video iframe container (hidden by default) -->
                        <div id="video-player-cat-{{ $article->id }}" class="aspect-video mb-3 -mx-4 -mt-4 hidden">
                            <iframe 
                                id="video-iframe-cat-{{ $article->id }}"
                                src="" 
                                class="w-full h-full"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                            </iframe>
                        </div>
                        @endif
                        
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-medium text-gray-950 dark:text-white text-sm leading-tight">{{ $article->title }}</h3>
                            @if($article->type === 'video')
                                <x-heroicon-o-play-circle class="w-5 h-5 text-danger-500 flex-shrink-0 ml-2" />
                            @else
                                <x-heroicon-o-document-text class="w-5 h-5 text-primary-500 flex-shrink-0 ml-2" />
                            @endif
                        </div>
                        
                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                            {{ Str::limit(strip_tags($article->content), 100) }}
                        </div>
                        
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $article->creator->name }}</span>
                            <div class="flex items-center space-x-2">
                                <x-heroicon-o-eye class="w-3 h-3" />
                                <span>{{ number_format($article->views_count) }}</span>
                                @if($article->type === 'video')
                                <span>•</span>
                                <a href="{{ route('knowledge.view', $article->id) }}" 
                                   target="_blank" 
                                   class="text-primary-600 dark:text-primary-400 hover:underline">
                                    เปิดหน้าเต็ม
                                </a>
                                @else
                                <span>•</span>
                                <a href="{{ route('knowledge.view', $article->id) }}" 
                                   target="_blank" 
                                   class="text-primary-600 dark:text-primary-400 hover:underline">
                                    อ่านเพิ่ม
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-filament::card>
                @endforeach
            </div>
        </x-filament::section>
        @endforeach

        @if(count($categories) === 0)
        <x-filament::section class="mt-6">
            <div class="text-center py-12">
                <x-heroicon-o-book-open class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-950 dark:text-white mb-2">ยังไม่มีบทความ</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">เริ่มสร้างบทความความรู้แรกของคุณเพื่อช่วยทีมงาน</p>
                
                <x-filament::button
                    href="{{ \App\Filament\Resources\KnowledgeArticleResource::getUrl('create') }}"
                    tag="a"
                    icon="heroicon-o-plus"
                >
                    สร้างบทความใหม่
                </x-filament::button>
            </div>
        </x-filament::section>
        @endif
    </div>
    
    <!-- JavaScript for inline video playback -->
    <script>
        let currentPlayingVideo = null;
        
        function playVideo(videoId, embedUrl) {
            // If there's a currently playing video, stop it
            if (currentPlayingVideo && currentPlayingVideo !== videoId) {
                stopVideo(currentPlayingVideo);
            }
            
            // Get elements
            const thumbnail = document.getElementById('video-thumb-' + videoId);
            const player = document.getElementById('video-player-' + videoId);
            const iframe = document.getElementById('video-iframe-' + videoId);
            
            if (thumbnail && player && iframe) {
                // Hide thumbnail, show player
                thumbnail.classList.add('hidden');
                player.classList.remove('hidden');
                
                // Set iframe src with autoplay
                iframe.src = embedUrl + '?autoplay=1';
                
                // Track current playing video
                currentPlayingVideo = videoId;
                
                // Increment views (optional)
                incrementVideoViews(videoId);
            }
        }
        
        function stopVideo(videoId) {
            const thumbnail = document.getElementById('video-thumb-' + videoId);
            const player = document.getElementById('video-player-' + videoId);
            const iframe = document.getElementById('video-iframe-' + videoId);
            
            if (thumbnail && player && iframe) {
                // Show thumbnail, hide player
                thumbnail.classList.remove('hidden');
                player.classList.add('hidden');
                
                // Clear iframe src to stop video
                iframe.src = '';
            }
        }
        
        function incrementVideoViews(videoId) {
            // Extract article ID from videoId (remove 'cat-' prefix if exists)
            const articleId = videoId.replace('cat-', '');
            
            // Send request to increment views
            fetch(`/admin/knowledge-articles/${articleId}/increment-views`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            }).then(response => {
                if (response.ok) {
                    // Optionally update the view count display
                    console.log('View count incremented');
                }
            });
        }
        
        // Stop video when clicking outside (optional)
        document.addEventListener('click', function(e) {
            if (currentPlayingVideo) {
                const player = document.getElementById('video-player-' + currentPlayingVideo);
                if (player && !player.contains(e.target) && !e.target.closest('[id^="video-thumb-"]')) {
                    // Uncomment to stop video when clicking outside
                    // stopVideo(currentPlayingVideo);
                    // currentPlayingVideo = null;
                }
            }
        });
    </script>
</x-filament-panels::page>