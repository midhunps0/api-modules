<?php

namespace Modules\Ynotz\EasyApi\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Pluralizer;
use Illuminate\Filesystem\Filesystem;

class MakeEasyapiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:easyapi {name} {cp?} {sp?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Easyapi Controller & Service classes for the give model name.
        Eg: php artisan make:easyapi ModelName ControllerClass/Path ServiceClass/Path';

    /**
     * Filesystem instance
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->getSourceFilePath('controller');

        $this->makeDirectory(dirname($path));

        $contents = $this->getSourceFile('controller');

        if (!$this->files->exists($path)) {
            $this->files->put($path, $contents);
            $this->info("File : {$path} created");
        } else {
            $this->info("File : {$path} already exits");
        }

        $path = $this->getSourceFilePath('service');

        $this->makeDirectory(dirname($path));

        $contents = $this->getSourceFile('service');

        if (!$this->files->exists($path)) {
            $this->files->put($path, $contents);
            $this->info("File : {$path} created");
        } else {
            $this->info("File : {$path} already exits");
        }

    }

    /**
     * Return the stub file path
     * @return string
     *
     */
    public function getStubPath($type)
    {
        $ds = DIRECTORY_SEPARATOR;
        if ($type == 'controller') {
            return __DIR__ . $ds.'..'.$ds.'stubs'.$ds.'controller.stub';
        }
        return __DIR__ . $ds.'..'.$ds.'stubs'.$ds.'service.stub';
    }

    /**
    **
    * Map the stub variables present in stub to its value
    *
    * @return array
    *
    */
    public function getStubVariables($type)
    {
        $ds = DIRECTORY_SEPARATOR;
        $arr = [];
        $classNameSingular = $this->getSingularClassName($this->argument('name'));
        $classNamePlural = Str::plural($classNameSingular);
        $classNamePluralLower = Str::lower($classNamePlural);

        $controllerDirectory = $this->argument('cp');
        $controllerDirectory = implode('\\', explode('/', $controllerDirectory));
        $controllerDirectory = $controllerDirectory != null ? '\\'.$controllerDirectory : '';
        $serviceDirectory = $this->argument('sp');
        $serviceDirectory = implode('\\', explode('/', $serviceDirectory));
        $serviceDirectory = $serviceDirectory != null ? '\\'.$serviceDirectory : '';

        $controllerNamespace = 'App\\Http\\Controllers'.$controllerDirectory;
        $serviceNamespace = 'App\\Services'.$serviceDirectory;

        switch($type) {
            case 'controller':
                $arr = [
                    'NAMESPACE'         => $controllerNamespace,
                    'CLASS_NAME'        => $classNameSingular,
                    'CLASS_NAME_PLURAL_LOWER' => $classNamePluralLower,
                    'CLASS_NAME_PLURAL' => $classNamePlural
                ];
                break;
            case 'service':
                $arr = [
                    'NAMESPACE'         => $serviceNamespace,
                    'CLASS_NAME'        => $classNameSingular,
                    'CLASS_NAME_PLURAL_LOWER' => $classNamePluralLower,
                    'CLASS_NAME_PLURAL' => $classNamePlural
                ];
                break;
        }
        return $arr;
    }

    /**
     * Get the stub path and the stub variables
     *
     * @return bool|mixed|string
     *
     */
    public function getSourceFile($type)
    {
        return $this->getStubContents($this->getStubPath($type), $this->getStubVariables($type));
    }


    /**
     * Replace the stub variables(key) with the desire value
     *
     * @param $stub
     * @param array $stubVariables
     * @return bool|mixed|string
     */
    public function getStubContents($stub , $stubVariables = [])
    {
        $contents = file_get_contents($stub);

        foreach ($stubVariables as $search => $replace)
        {
            $contents = str_replace('$'.$search.'$' , $replace, $contents);
        }

        return $contents;

    }

    /**
     * Get the full path of generate class
     *
     * @return string
     */
    public function getSourceFilePath($type)
    {
        $ds = DIRECTORY_SEPARATOR;

        $controllerDirectory = $this->argument('cp');
        $cdirPath = '';
        if($controllerDirectory != null) {
            $cdirPath = implode($ds, explode('/',$controllerDirectory)).$ds;
        }

        $serviceDirectory = $this->argument('sp');
        $sdirPath = '';
        if($serviceDirectory != null) {
            $sdirPath = implode($ds, explode('/',$serviceDirectory)).$ds;
        }


        if ($type == 'controller') {
            return app_path().$ds.'Http'.$ds.'Controllers' .$ds.$cdirPath.$this->getSingularClassName($this->argument('name')) . 'Controller.php';
        }
        return app_path().$ds.'Services'.$ds.$sdirPath.$this->getSingularClassName($this->argument('name')) . 'Service.php';
    }

    /**
     * Return the Singular Capitalize Name
     * @param $name
     * @return string
     */
    public function getSingularClassName($name)
    {
        return ucwords(Pluralizer::singular($name));
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

}
