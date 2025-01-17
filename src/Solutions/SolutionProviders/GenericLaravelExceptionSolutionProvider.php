<?php

namespace Spatie\LaravelIgnition\Solutions\SolutionProviders;

use Illuminate\Broadcasting\BroadcastException;
use Spatie\IgnitionContracts\BaseSolution;
use Spatie\IgnitionContracts\HasSolutionsForThrowable;
use Spatie\LaravelIgnition\Support\LaravelVersion;
use Throwable;

class GenericLaravelExceptionSolutionProvider implements HasSolutionsForThrowable
{
    public function canSolve(Throwable $throwable): bool
    {
        return ! is_null($this->getSolutionTexts($throwable));
    }

    public function getSolutions(Throwable $throwable): array
    {
        if (! $texts = $this->getSolutionTexts($throwable)) {
            return [];
        }

        $solution = BaseSolution::create($texts['title'])
            ->setSolutionDescription($texts['description'])
            ->setDocumentationLinks($texts['links']);

        return ([$solution]);
    }

    protected function getSolutionTexts(Throwable $throwable) : ?array
    {
        foreach ($this->getSupportedExceptions() as $supportedClass => $texts) {
            if ($throwable instanceof $supportedClass) {
                return $texts;
            }
        }

        return null;
    }

    protected function getSupportedExceptions()
    {
        $majorVersion = LaravelVersion::major();

        return
        [
            BroadcastException::class => [
                'title' => 'Here are some links that might help solve this problem',
                'description' => '',
                'links' => [
                    'Laravel docs on authentication' => "https://laravel.com/docs/{$majorVersion}.x/authentication",
                ],
            ],
        ];
    }
}
