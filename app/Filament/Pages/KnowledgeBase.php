<?php

namespace App\Filament\Pages;

use App\Models\KnowledgeArticle;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;

class KnowledgeBase extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'ศูนย์ความรู้';
    protected static ?string $title = 'ศูนย์ความรู้';
    protected static ?string $navigationGroup = 'Knowledge Sharing';
    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.knowledge-base';
    
    public $selectedArticle = null;
    public $showModal = false;

    public function getViewData(): array
    {
        $categories = KnowledgeArticle::CATEGORIES;
        $articles = [];
        
        foreach ($categories as $slug => $name) {
            $categoryArticles = KnowledgeArticle::published()
                ->byCategory($slug)
                ->with('creator')
                ->orderBy('created_at', 'desc')
                ->get();
                
            if ($categoryArticles->isNotEmpty()) {
                $articles[$slug] = [
                    'name' => $name,
                    'articles' => $categoryArticles
                ];
            }
        }
        
        $featuredVideos = KnowledgeArticle::published()
            ->byType(KnowledgeArticle::TYPE_VIDEO)
            ->orderBy('views_count', 'desc')
            ->take(6)
            ->get();
        
        return [
            'categories' => $articles,
            'featuredVideos' => $featuredVideos,
        ];
    }
    
    public function openArticle($articleId)
    {
        $article = KnowledgeArticle::find($articleId);
        if ($article) {
            $article->incrementViews();
            $this->selectedArticle = $article;
            $this->showModal = true;
            
            // Open in new tab for now
            $this->redirect(route('knowledge.view', $article->id), navigate: false);
        }
    }
}
