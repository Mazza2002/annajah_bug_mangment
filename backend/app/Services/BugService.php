<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\BugStatusHistory;
use App\Models\BugComment;
use App\Models\BugAttachment;
use App\Models\BugLink;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class BugService
{
    public function createBug(array $data, ?array $tags = null)
    {
        return DB::transaction(function () use ($data, $tags) {
            $bug = Bug::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'steps_to_reproduce' => $data['steps_to_reproduce'] ?? null,
                'severity' => $data['severity'] ?? 'Medium',
                'status' => 'Open',
                'project_id' => $data['project_id'],
                'reporter_id' => Auth::id(),
                'assignee_id' => $data['assignee_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'environment' => $data['environment'] ?? null,
            ]);

            if ($tags) {
                $bug->tags()->sync($tags);
            }

            $this->logStatusChange($bug, null, 'Open', 'Bug created');
            $this->logActivity('created', 'bug', $bug->id, ['title' => $bug->title]);

            return $bug;
        });
    }

    public function updateBug(Bug $bug, array $data, ?array $tags = null)
    {
        return DB::transaction(function () use ($bug, $data, $tags) {
            $bug->update($data);

            if ($tags !== null) {
                $bug->tags()->sync($tags);
            }

            $this->logActivity('updated', 'bug', $bug->id, ['fields' => array_keys($data)]);

            return $bug;
        });
    }

    public function updateStatus(Bug $bug, string $newStatus, string $note)
    {
        return DB::transaction(function () use ($bug, $newStatus, $note) {
            $oldStatus = $bug->status;
            
            if ($oldStatus === $newStatus) {
                return $bug;
            }

            // State Machine Rules
            $allowedTransitions = [
                'Open' => ['In Progress', 'Not Fixed', 'Fixed', 'Closed', 'Reopened'],
                'In Progress' => ['Fixed', 'Not Fixed', 'Closed', 'Open', 'Reopened'],
                'Fixed' => ['Closed', 'Reopened', 'In Progress', 'Open'],
                'Not Fixed' => ['Closed', 'Reopened', 'In Progress', 'Open'],
                'Reopened' => ['In Progress', 'Fixed', 'Closed', 'Open'],
                'Closed' => ['Reopened', 'Open', 'In Progress'],
            ];

            if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
                throw new \Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
            }

            $bug->update(['status' => $newStatus]);

            $this->logStatusChange($bug, $oldStatus, $newStatus, $note);
            $this->logActivity('status_changed', 'bug', $bug->id, ['from' => $oldStatus, 'to' => $newStatus]);

            // Notifications will be handled by Event Listeners in a real app
            return $bug;
        });
    }

    public function assignBug(Bug $bug, int $userId)
    {
        return DB::transaction(function () use ($bug, $userId) {
            $bug->update(['assignee_id' => $userId]);
            $this->logActivity('assigned', 'bug', $bug->id, ['assignee_id' => $userId]);
            return $bug;
        });
    }

    public function addComment(Bug $bug, string $content, bool $isInternalNote = false)
    {
        return DB::transaction(function () use ($bug, $content, $isInternalNote) {
            $comment = BugComment::create([
                'bug_id' => $bug->id,
                'user_id' => Auth::id(),
                'content' => $content,
                'is_internal_note' => $isInternalNote,
            ]);

            $this->logActivity('commented', 'bug', $bug->id, ['comment_id' => $comment->id]);

            return $comment;
        });
    }

    public function uploadAttachment(Bug $bug, UploadedFile $file)
    {
        $path = $file->store('bug_attachments', 'public');

        return BugAttachment::create([
            'bug_id' => $bug->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    public function deleteAttachment(BugAttachment $attachment)
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
    }

    public function linkBug(Bug $sourceBug, int $targetBugId, string $relationType)
    {
        return BugLink::create([
            'source_bug_id' => $sourceBug->id,
            'target_bug_id' => $targetBugId,
            'relation_type' => $relationType,
        ]);
    }

    public function findPotentialDuplicates(string $title, int $projectId)
    {
        $keywords = array_filter(explode(' ', strtolower($title)), fn($k) => strlen($k) > 3);

        if (empty($keywords)) return collect();

        return Bug::where('project_id', $projectId)
            ->where(function($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('title', 'like', "%$word%");
                }
            })
            ->limit(5)
            ->get();
    }

    public function suggestSeverity(string $title, string $description)
    {
        $text = strtolower($title . ' ' . $description);
        
        $criticalKeywords = ['crash', 'security', 'leak', 'fatal', 'down', 'broken', 'emergency', 'panic'];
        $highKeywords = ['error', 'failure', 'missing', 'failed', 'issue'];
        
        foreach ($criticalKeywords as $word) {
            if (str_contains($text, $word)) return 'Critical';
        }
        
        foreach ($highKeywords as $word) {
            if (str_contains($text, $word)) return 'High';
        }
        
        return 'Medium';
    }

    protected function logStatusChange(Bug $bug, ?string $from, string $to, ?string $note)
    {
        BugStatusHistory::create([
            'bug_id' => $bug->id,
            'user_id' => Auth::id(),
            'old_status' => $from,
            'new_status' => $to,
            'note' => $note,
        ]);
    }

    protected function logActivity(string $action, string $entityType, int $entityId, array $properties = [])
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'properties' => $properties,
        ]);
    }
}
