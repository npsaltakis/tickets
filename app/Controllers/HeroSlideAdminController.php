<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\HeroSlideModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Admin → Hero slider: slides of the home page slider, each an uploaded image or an event.
 */
class HeroSlideAdminController extends BaseController
{
    private const UPLOAD_DIR = 'assets/images/hero';

    private HeroSlideModel $slideModel;

    public function __construct()
    {
        $this->slideModel = new HeroSlideModel();
    }

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $editId = (int) $this->request->getGet('edit');
        $events = $this->selectableEvents();

        return view('admin/hero_slides', [
            'slides'      => $this->slideModel->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll(),
            'editing'     => $editId > 0 ? $this->slideModel->find($editId) : null,
            'events'      => $events,
            'eventTitles' => array_column($events, 'title', 'id'),
            'pageTitle'   => lang('App.heroSlidesTitle'),
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        return $this->persist(null);
    }

    public function update(int $id): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $slide = $this->slideModel->find($id);
        if ($slide === null) {
            return redirect()->to(base_url('admin/hero-slides'));
        }

        return $this->persist($slide);
    }

    /**
     * Saves the order set by dragging the rows of the list (ids in their new order).
     */
    public function reorder(): ResponseInterface
    {
        if (! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('ids')))));
        if ($ids === []) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'No slides']);
        }

        $known = array_map('intval', array_column($this->slideModel->select('id')->whereIn('id', $ids)->findAll(), 'id'));
        $order = 0;
        foreach ($ids as $id) {
            if (in_array($id, $known, true)) {
                $this->slideModel->update($id, ['sort_order' => ++$order]);
            }
        }

        $this->logAdminAction('hero_slide_reorder', 'system', ['ids' => $ids]);

        return $this->response->setJSON(['success' => true]);
    }

    public function delete(int $id): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $slide = $this->slideModel->find($id);
        if ($slide !== null) {
            $this->deleteUpload((string) ($slide['image'] ?? ''));
            $this->slideModel->delete($id);
            $this->logAdminAction('hero_slide_delete', 'system', ['id' => $id]);
        }

        return redirect()->to(base_url('admin/hero-slides'))->with('hero_info', lang('App.heroSlideDeleted'));
    }

    private function persist(?array $slide): RedirectResponse
    {
        // withInput() flashes the form at once, so it is only called on the error paths.
        $backUrl = base_url('admin/hero-slides' . ($slide ? '?edit=' . (int) $slide['id'] : ''));
        $back    = static fn (string $error): RedirectResponse => redirect()->to($backUrl)->withInput()->with('hero_error', $error);

        $type    = $this->request->getPost('type') === 'event' ? 'event' : 'image';
        $eventId = (int) $this->request->getPost('event_id');
        $linkUrl = trim((string) $this->request->getPost('link_url'));

        if ($type === 'event' && ! array_key_exists($eventId, array_column($this->selectableEvents(), 'title', 'id'))) {
            return $back(lang('App.heroSlideEventRequired'));
        }
        if ($linkUrl !== '' && preg_match('#^(https?://|/)#i', $linkUrl) !== 1) {
            return $back(lang('App.heroSlideLinkInvalid'));
        }

        $image = (string) ($slide['image'] ?? '');
        if ($this->request->getPost('remove_image') === '1') {
            $this->deleteUpload($image);
            $image = '';
        }

        $file = $this->request->getFile('image');
        if ($file instanceof UploadedFile && $file->isValid() && ! $file->hasMoved()) {
            $stored = $this->storeImage($file);
            if ($stored === null) {
                return $back(lang('App.heroSlideImageInvalid'));
            }
            $this->deleteUpload($image);
            $image = $stored;
        }

        // An event slide falls back to the event's own image; an image slide needs one.
        if ($type === 'image' && $image === '') {
            return $back(lang('App.heroSlideImageRequired'));
        }

        $data = [
            'type'        => $type,
            'event_id'    => $type === 'event' ? $eventId : null,
            'image'       => $image !== '' ? $image : null,
            'title'       => mb_substr(trim((string) $this->request->getPost('title')), 0, 191),
            'subtitle'    => mb_substr(trim((string) $this->request->getPost('subtitle')), 0, 255),
            'link_url'    => mb_substr($linkUrl, 0, 255),
            'button_text' => mb_substr(trim((string) $this->request->getPost('button_text')), 0, 60),
            'sort_order'  => (int) $this->request->getPost('sort_order'),
            'is_active'   => $this->request->getPost('is_active') === '1' ? 1 : 0,
        ];

        if ($slide === null) {
            $this->slideModel->insert($data);
            $this->logAdminAction('hero_slide_create', 'system', ['type' => $type, 'event_id' => $data['event_id']]);
            $message = lang('App.heroSlideCreated');
        } else {
            $this->slideModel->update((int) $slide['id'], $data);
            $this->logAdminAction('hero_slide_update', 'system', ['id' => (int) $slide['id']]);
            $message = lang('App.heroSlideUpdated');
        }

        return redirect()->to(base_url('admin/hero-slides'))->with('hero_info', $message);
    }

    /**
     * Events that can be put on a slide: not deleted, public and not over yet.
     *
     * @return list<array{id: int, title: string, start_date: ?string}>
     */
    private function selectableEvents(): array
    {
        $rows = (new EventModel())->builder()
            ->select('id, title, start_date')
            ->where('deleted_at', null)
            ->where('is_private', 0)
            ->groupStart()
                ->where('end_date >=', date('Y-m-d H:i:s'))
                ->orWhere('end_date', null)
            ->groupEnd()
            ->orderBy('start_date', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'id'         => (int) $row['id'],
            'title'      => (string) $row['title'],
            'start_date' => $row['start_date'] ?? null,
        ], $rows);
    }

    private function storeImage(UploadedFile $file): ?string
    {
        $extension = strtolower((string) $file->getExtension());

        if ($file->getSize() > 5 * 1024 * 1024
            || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
            || ! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)
            || @getimagesize($file->getTempName()) === false) {
            return null;
        }

        $directory = FCPATH . self::UPLOAD_DIR;
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return null;
        }

        $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $name = time() . '_' . bin2hex(random_bytes(10)) . '.' . ($mimeMap[$file->getMimeType()] ?? 'jpg');
        $file->move($directory, $name, true);

        return self::UPLOAD_DIR . '/' . $name;
    }

    private function deleteUpload(string $path): void
    {
        // Only files this page uploaded are removed.
        if ($path !== '' && str_starts_with($path, self::UPLOAD_DIR . '/') && ! str_contains($path, '..')) {
            @unlink(FCPATH . $path);
        }
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
