<?php
/**
 * Migration 017 — Ensure upload subdirectories exist
 * Safe to run multiple times — uses is_dir() check before mkdir().
 * This migration ONLY creates folders if they don't already exist.
 */
return [
    'id'          => '017',
    'title'       => 'Create upload folder structure (idempotent)',
    'description' => 'Creates assets/uploads/banners, general, products, shops if missing.',
    'steps'       => [
        '017a' => [
            'label' => 'Create upload subdirectories',
            'php'   => function() {
                $base = defined('UPLOAD_PATH') ? UPLOAD_PATH : dirname(__DIR__) . '/assets/uploads';
                $dirs = ['banners', 'general', 'products', 'shops'];
                $created = [];
                foreach ($dirs as $dir) {
                    $path = $base . '/' . $dir;
                    if (!is_dir($path)) {
                        mkdir($path, 0755, true);
                        $created[] = $dir;
                    }
                }
                // Add .gitkeep to each so they survive in version control
                foreach ($dirs as $dir) {
                    $keep = $base . '/' . $dir . '/.gitkeep';
                    if (!file_exists($keep)) file_put_contents($keep, '');
                }
                return empty($created)
                    ? 'All upload folders already exist — nothing to do.'
                    : 'Created: ' . implode(', ', $created);
            },
        ],
    ],
];
