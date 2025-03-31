<?php

namespace App\Livewire;

use App\Models\Ripple;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class RippleFeed extends Component
{
    use WithFileUploads;
    public $content;
    public $file;
    public $showMenu = [];
    public $editingRipple = null;
    public $editingContent = '';
    public $newContent = '';
    public $editingRippleId;
    public $editingFile;
    public $replyingTo = null;
    public $replyContent = [];
    public array $openReplies = [];

public function toggleMenu($rippleId)
{
    $this->showMenu[$rippleId] = !($this->showMenu[$rippleId] ?? false);
}


    public function render()
{
    return view('livewire.ripple-feed', [
        'ripples' => Ripple::with('user', 'likes')
            ->orderByDesc('pinned') // Pinned at the top
            ->latest() // Latest posts after pinned ones
            ->get(),
    ]);
}


public function postRipple()
{
    $this->validate([
        'newContent' => 'required|string|max:255',
        'file' => 'nullable|file|max:10240', // 10MB limit
    ]);

    // $filePath = $this->file ? $this->file->store('ripples', 'public') : null;
    $filePath = $this->file?->store('ripples', 'public');

    Ripple::create([
        'user_id' => auth()->id(),
        'content' => $this->newContent,
        'file_path' => $filePath,
    ]);

    $this->reset(['newContent', 'file']);

    session()->flash('success', 'Ripple posted successfully!');
}


public function editRipple($rippleId)
    {
        $ripple = Ripple::findOrFail($rippleId);

        $this->editingRippleId = $rippleId;
        $this->editingContent = $ripple->content;
        $this->editingFile = null;

        // $this->dispatch('editing-started', id: $rippleId);
    }

    // Save the edits (including file upload)
    public function saveRipple()
    {
        $this->validate([
            'editingContent' => 'required|string|max:255',
            'editingFile' => 'nullable|file|max:10240|mimes:jpeg,png,pdf', 
        ]);
        
        $ripple = Ripple::findOrFail($this->editingRippleId);

        // Ensure the user owns this ripple
        if ($ripple->user_id !== auth()->id()) {
            return;
        }

        // Handle content
        $ripple->content = $this->editingContent;

        // Handle file upload if a new one is provided
        if ($this->editingFile) {
            if ($ripple->file_path) {
                Storage::disk('public')->delete($ripple->file_path);
            }
            $ripple->file_path = $this->editingFile->store('ripples', 'public');
        }
        Log::info($ripple->file_path);
        $ripple->save();

        // Reset editing state
        $this->reset(['editingRippleId', 'editingContent', 'editingFile']);
        session()->flash('success', 'Ripple updated successfully!');
    }

    public function cancelEdit()
    {
        $this->reset(['editingRippleId', 'editingContent', 'editingFile']);
    }
    

public function deleteRipple($rippleId)
{
    Ripple::find($rippleId)->delete();

    // Reset editing and ensure the menu is closed
    $this->reset(['editingRipple']);
    $this->dispatch('menu-closed');
}



    public function likeRipple($id)
    {
        $ripple = Ripple::find($id);
        $ripple->likes += 1;
        $ripple->save();
    }

    public function toggleLike($rippleId)
{
    $ripple = Ripple::find($rippleId);

    // Check if the user has already liked the ripple
    if ($ripple->isLikedByUser(auth()->id())) {
        // Remove like if it exists
        $ripple->likes()->where('user_id', auth()->id())->delete();
    } else {
        // Add a new like if not already liked
        $ripple->likes()->create(['user_id' => auth()->id()]);
    }
}
public function togglePin($rippleId)
{
    $ripple = Ripple::findOrFail($rippleId);
    
    if (auth()->user()->hasAnyRole(['Admin', 'PNC'])) {
        // dd('You are authorized to pin/unpin ripples');
        $ripple->pinned = !$ripple->pinned;
        $ripple->save();

        // Refresh ripples to reflect the new sort order
        $this->ripples = Ripple::with('user', 'likes')
            ->orderByDesc('pinned')
            ->latest()
            ->get();
        
        return back()->with('notify', [
            'type' => 'success',
            'message' => $ripple->pinned ? 'Ripple pinned successfully!' : 'Ripple unpinned successfully!'
        ]);
        // $this->dispatch('alert', type: 'success', message: $ripple->pinned ? 'Ripple pinned successfully!' : 'Ripple unpinned successfully!');
    }
    
}
public function deleteAttachment($rippleId)
{
    $ripple = Ripple::findOrFail($rippleId);

    if ($ripple->file_path && $ripple->user_id === auth()->id()) {
        Storage::disk('public')->delete($ripple->file_path);

        $ripple->update(['file_path' => null]);

        session()->flash('success', 'Attachment deleted successfully!');
    }
}

/**Replies */
public function toggleReply($rippleId)
{
    $this->replyingTo = $this->replyingTo === $rippleId ? null : $rippleId;
}

public function toggleReplies($rippleId)
{
    // Toggle the replies open/closed state per ripple
    $this->openReplies[$rippleId] = !($this->openReplies[$rippleId] ?? false);
}

public function submitReply($rippleId)
{
    $this->validate([
        'replyContent' => 'required|string|max:500',
    ]);

    Ripple::create([
        'user_id' => auth()->id(),
        'content' => $this->replyContent,
        'parent_id' => $rippleId,
    ]);

    $this->replyContent = '';
    $this->replyingTo = null;
    $this->dispatch('rippleUpdated');

}



}
