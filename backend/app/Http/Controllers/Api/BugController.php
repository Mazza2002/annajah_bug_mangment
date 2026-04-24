<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bug;
use App\Models\BugAttachment;
use App\Services\BugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BugController extends Controller
{
    protected $bugService;

    public function __construct(BugService $bugService)
    {
        $this->bugService = $bugService;
    }

    public function index(Request $request)
    {
        $query = Bug::with(['project', 'reporter', 'assignee', 'category', 'tags']);

        if ($request->has('status')) $query->where('status', $request->status);
        if ($request->has('severity')) $query->where('severity', $request->severity);
        if ($request->has('project_id')) $query->where('project_id', $request->project_id);
        if ($request->has('assignee_id')) $query->where('assignee_id', $request->assignee_id);
        if ($request->has('reporter_id')) $query->where('reporter_id', $request->reporter_id);
        if ($request->has('category_id')) $query->where('category_id', $request->category_id);
        
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortColumn, $sortDirection);

        return $query->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'steps_to_reproduce' => 'nullable|string',
            'severity' => 'nullable|in:Low,Medium,High,Critical',
            'project_id' => 'required|exists:projects,id',
            'assignee_id' => 'nullable|exists:users,id',
            'category_id' => 'nullable|exists:categories,id',
            'environment' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $bug = $this->bugService->createBug($validated, $validated['tags'] ?? null);

        return response()->json($bug->load(['project', 'reporter', 'assignee', 'tags', 'category']), 201);
    }

    public function show(Bug $bug)
    {
        return $bug->load([
            'project', 'reporter', 'assignee', 'tags', 'category',
            'comments.user', 'statusHistory.user', 'attachments', 
            'linksAsSource.targetBug', 'linksAsTarget.sourceBug'
        ]);
    }

    public function update(Request $request, Bug $bug)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'steps_to_reproduce' => 'nullable|string',
            'severity' => 'sometimes|in:Low,Medium,High,Critical',
            'category_id' => 'nullable|exists:categories,id',
            'environment' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $bug = $this->bugService->updateBug($bug, $validated, $validated['tags'] ?? null);

        return response()->json($bug->load(['tags', 'category']));
    }

    public function updateStatus(Request $request, Bug $bug)
    {
        $validated = $request->validate([
            'status' => 'required|in:Open,In Progress,Fixed,Not Fixed,Closed,Reopened',
            'note' => 'required|string',
            'resolution' => 'nullable|string',
        ]);

        try {
            if ($validated['status'] === 'Fixed' && !empty($validated['resolution'])) {
                $bug->update(['resolution' => $validated['resolution']]);
            }
            $this->bugService->updateStatus($bug, $validated['status'], $validated['note']);
            return response()->json($bug->fresh(['statusHistory.user']));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function assign(Request $request, Bug $bug)
    {
        $validated = $request->validate([
            'assignee_id' => 'required|exists:users,id',
        ]);

        $this->bugService->assignBug($bug, $validated['assignee_id']);

        return response()->json($bug->fresh(['assignee']));
    }

    public function addComment(Request $request, Bug $bug)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'is_internal_note' => 'boolean',
        ]);

        $comment = $this->bugService->addComment($bug, $validated['content'], $validated['is_internal_note'] ?? false);

        return response()->json($comment->load('user'), 201);
    }

    public function uploadAttachment(Request $request, Bug $bug)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $attachment = $this->bugService->uploadAttachment($bug, $request->file('file'));

        return response()->json($attachment, 201);
    }

    public function deleteAttachment(Request $request, $id)
    {
        $attachment = BugAttachment::findOrFail($id);
        
        // Basic auth check
        if (Auth::user()->role->name !== 'Admin' && Auth::id() !== $attachment->bug->reporter_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->bugService->deleteAttachment($attachment);

        return response()->json(null, 204);
    }

    public function linkBug(Request $request, Bug $bug)
    {
        $validated = $request->validate([
            'target_bug_id' => 'required|exists:bugs,id|different:'.$bug->id,
            'relation_type' => 'required|in:duplicate,related,blocks',
        ]);

        $link = $this->bugService->linkBug($bug, $validated['target_bug_id'], $validated['relation_type']);

        return response()->json($link, 201);
    }

    public function checkDuplicates(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'project_id' => 'required|exists:projects,id',
        ]);

        $duplicates = $this->bugService->findPotentialDuplicates($request->title, $request->project_id);
        return response()->json($duplicates);
    }

    public function suggestSeverity(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        $severity = $this->bugService->suggestSeverity($request->title, $request->description);
        return response()->json(['severity' => $severity]);
    }

    public function destroy(Bug $bug)
    {
        $bug->delete();
        return response()->json(null, 204);
    }
}
