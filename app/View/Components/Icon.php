<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Icon extends Component
{
    private static array $svgBodies = [];

    public readonly string $svgBody;

    public function __construct(public readonly string $name)
    {
        $safeName = preg_match('/^[a-z0-9-]+$/', $name) ? $name : 'circle-help';
        $path = resource_path('icons/'.$safeName.'.svg');
        if (! is_file($path)) {
            $path = resource_path('icons/circle-help.svg');
        }

        $this->svgBody = self::$svgBodies[$path] ??= file_get_contents($path);
    }

    public function render(): View
    {
        return view('components.icon');
    }
}
