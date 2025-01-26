<?php

namespace Wink\ModelGenerator\Generators;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class PolicyGenerator
{
    protected Filesystem $files;
    protected string $stubPath;
    protected string $namespace;
    protected string $modelNamespace;
    protected string $policyPath;

    public function __construct(
        Filesystem $files,
        string $stubPath,
        string $namespace,
        string $modelNamespace,
        string $policyPath
    ) {
        $this->files = $files;
        $this->stubPath = $stubPath;
        $this->namespace = $namespace;
        $this->modelNamespace = $modelNamespace;
        $this->policyPath = $policyPath;
    }

    public function generate(string $modelName): string
    {
        if (!$this->files->exists($this->stubPath . '/policy.stub')) {
            throw new \RuntimeException('Policy stub file not found');
        }

        $stub = $this->files->get($this->stubPath . '/policy.stub');
        $className = $modelName . 'Policy';
        $filePath = $this->policyPath . '/' . $className . '.php';

        if (!$this->files->isDirectory($this->policyPath)) {
            $this->files->makeDirectory($this->policyPath, 0755, true);
        }

        $content = $this->replaceStubVariables($stub, [
            'namespace' => $this->namespace,
            'modelNamespace' => $this->modelNamespace,
            'model' => $modelName,
            'modelVariable' => Str::camel($modelName),
        ]);

        $this->files->put($filePath, $content);
        return $content;
    }

    protected function replaceStubVariables(string $stub, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', $value, $stub);
        }

        return $stub;
    }
}