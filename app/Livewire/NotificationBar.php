<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationBar extends Component
{
    public $notifications = [];
    public $show = false;

    protected $listeners = [
        'showNotification' => 'addNotification',
        'hideNotification' => 'removeNotification'
    ];

    public function mount()
    {
        $this->notifications = session()->get('notifications', []);
        $this->show = !empty($this->notifications);
    }

    public function addNotification($type, $message, $duration = 5000)
    {
        $id = uniqid();
        $notification = [
            'id' => $id,
            'type' => $type, // success, error, warning, info
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        $this->notifications[] = $notification;
        $this->show = true;

        // Auto remove after duration
        if ($duration > 0) {
            $this->dispatch('auto-remove-notification', ['id' => $id, 'duration' => $duration]);
        }
    }

    public function removeNotification($id)
    {
        $this->notifications = array_filter($this->notifications, function($notification) use ($id) {
            return $notification['id'] !== $id;
        });

        if (empty($this->notifications)) {
            $this->show = false;
        }
    }

    public function clearAll()
    {
        $this->notifications = [];
        $this->show = false;
        session()->forget('notifications');
    }

    public function getIcon($type)
    {
        $icons = [
            'success' => 'fas fa-check-circle',
            'error' => 'fas fa-exclamation-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'info' => 'fas fa-info-circle'
        ];
        
        return $icons[$type] ?? 'fas fa-bell';
    }

    public function getColor($type)
    {
        $colors = [
            'success' => 'bg-green-50 border-green-200 text-green-800',
            'error' => 'bg-red-50 border-red-200 text-red-800',
            'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
            'info' => 'bg-blue-50 border-blue-200 text-blue-800'
        ];
        
        return $colors[$type] ?? 'bg-gray-50 border-gray-200 text-gray-800';
    }

    public function render()
    {
        return view('livewire.notification-bar');
    }
}
