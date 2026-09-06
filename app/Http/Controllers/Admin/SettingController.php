<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\MediaService;
use App\Support\SettingDefinitions;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SettingController extends Controller
{
    public function __construct(
        private readonly Settings $settings,
        private readonly AuditLogger $audit,
        private readonly MediaService $media,
    ) {}

    public function edit(?string $group = null): View
    {
        $this->authorize('settings.manage');

        $groups = SettingDefinitions::groups();
        $group ??= array_key_first($groups);

        if (! array_key_exists($group, $groups)) {
            throw new NotFoundHttpException;
        }

        $definitions = collect(SettingDefinitions::all())
            ->filter(fn (array $definition) => $definition['group'] === $group);

        return view('admin.settings.edit', [
            'groups' => $groups,
            'group' => $group,
            'definitions' => $definitions,
            'values' => $this->settings->all(),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $this->authorize('settings.manage');

        $definitions = collect(SettingDefinitions::all())
            ->filter(fn (array $definition) => $definition['group'] === $group);

        if ($definitions->isEmpty()) {
            throw new NotFoundHttpException;
        }

        $rules = [];
        $input = [];

        foreach ($definitions as $key => $definition) {
            $field = $this->fieldName($key);

            $rules[$field] = match ($definition['type']) {
                SettingDefinitions::TYPE_BOOLEAN => ['boolean'],
                SettingDefinitions::TYPE_INTEGER => ['nullable', 'integer', 'min:0', 'max:100000'],
                SettingDefinitions::TYPE_TEXT => ['nullable', 'string', 'max:5000'],
                SettingDefinitions::TYPE_JSON => ['nullable', 'string', 'max:8000'],
                SettingDefinitions::TYPE_COLOUR => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                SettingDefinitions::TYPE_IMAGE => [
                    'nullable', 'image',
                    'max:'.(int) config('portlane.uploads.image_max_kb'),
                    'mimes:'.implode(',', (array) config('portlane.uploads.image_mimes')),
                ],
                default => ['nullable', 'string', 'max:500'],
            };
        }

        $validated = $request->validate($rules);

        foreach ($definitions as $key => $definition) {
            $field = $this->fieldName($key);

            $input[$key] = match ($definition['type']) {
                SettingDefinitions::TYPE_BOOLEAN => $request->boolean($field),
                SettingDefinitions::TYPE_JSON => $this->parseLines((string) ($validated[$field] ?? '')),
                SettingDefinitions::TYPE_IMAGE => $this->imageValue($request, $field, $key),
                default => $validated[$field] ?? null,
            };

            if ($definition['type'] === SettingDefinitions::TYPE_IMAGE && $input[$key] === false) {
                unset($input[$key]);
            }
        }

        $this->settings->setMany($input);

        $this->audit->record(
            'settings.updated',
            null,
            'Updated the '.(SettingDefinitions::groups()[$group] ?? $group).' settings',
            ['group' => $group, 'keys' => array_keys($input)],
        );

        return redirect()->route('admin.settings.edit', $group)->with('status', 'Settings saved.');
    }

    /**
     * Turns "Title | Explanation" lines into the stored list structure.
     *
     * @return list<array{title: string, body: string}>
     */
    private function parseLines(string $value): array
    {
        return collect(preg_split('/\R/', $value) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line): array {
                [$title, $body] = array_pad(explode('|', $line, 2), 2, '');

                return ['title' => trim($title), 'body' => trim($body)];
            })
            ->values()
            ->all();
    }

    /**
     * Returns the new path, null when the image is being cleared, or false
     * when the stored value should be left untouched.
     */
    private function imageValue(Request $request, string $field, string $key): string|null|false
    {
        if ($request->hasFile($field)) {
            return $this->media->replace($this->settings->string($key) ?: null, $request->file($field), 'branding');
        }

        if ($request->boolean('remove_'.$field)) {
            $this->media->delete($this->settings->string($key) ?: null);

            return null;
        }

        return false;
    }

    private function fieldName(string $key): string
    {
        return str_replace('.', '_', $key);
    }
}
