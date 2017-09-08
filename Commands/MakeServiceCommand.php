<?php

namespace OkayBueno\ServiceGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Class MakeServiceCommand
 * @package OkayBueno\ServiceGenerator\Commands
 */
class MakeServiceCommand extends Command
{

    protected $signature = 'make:service {service}';
    protected $description = 'Interactively create a new service.';

    protected $filesystem;
    protected $composer;

    protected $serviceGroup;
    protected $serviceBasePath;
    protected $serviceNamespace;
    protected $serviceInterfaceName;
    protected $serviceClassPath;
    protected $serviceClassName;
    protected $validatorBasePath;
    protected $validatorInterfaceNamespace;
    protected $validatorInterfaceName;
    protected $validatorClassPath;
    protected $validatorClassName;
    protected $repositoryFullName;
    protected $repositoryName;


    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        Filesystem $filesystem
    )
    {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->composer = app()['composer'];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $service = $this->argument('service');

        if ( $service )
        {
            $groups = config('service-generator.groups');

            $groupKeys = array_keys( $groups );

            $group = $this->choice('For which group do you want to create the service?', $groupKeys );

            $repository = $this->confirm('Do you want to inject a repository to this service?', false);

            if ( $repository ) $repository = $this->ask('Please specify the full interface (with namespace) for the repository that you want to inject (ie: MyApp\Repositories\MyRepositoryInterface)');
            else $repository = FALSE;

            $validator = $this->confirm('Do you want to create and inject a validator for this service?', true);

            $this->populateValuesForProperties( $service, $group, $repository, $validator );

            // Create validator, if it proceeds.
            if ( $validator ) $this->createValidatorWithInterface();

            // Create service.
            $this->createServiceWithInterface();

            // And lastly, service provide.
            $this->createServiceProvider();

        } else
        {
            $this->error('Please introduce a name for this service.');
        }
    }

    /**
     * @param $service
     * @param $group
     * @param $repository
     * @param $validator
     */
    protected function populateValuesForProperties( $service, $group, $repository, $validator )
    {
        $groups = config('service-generator.groups');

        $serviceName = $service.'Service';

        $this->serviceGroup = $group;
        $this->serviceBasePath = $groups[ $group ].'/'.$service;
        $this->serviceNamespace = $group.'\\'.$service;
        $this->serviceInterfaceName = $serviceName.'Interface';
        $this->serviceClassPath = rtrim($this->serviceBasePath, '/').'/src';
        $this->serviceClassName = $serviceName;

        if ( $validator )
        {
            $this->validatorBasePath = rtrim($this->serviceBasePath, '/').'/Validation';
            $this->validatorClassPath = rtrim($this->validatorBasePath, '/').'/src';
            $this->validatorInterfaceNamespace = $this->serviceNamespace.'\Validation';
            $this->validatorInterfaceName = $service.'ValidatorInterface';
            $this->validatorClassName = $service.'LaravelValidator';
        }

        if ( $repository )
        {
            $rplc = str_replace( '\\', '/', $repository );
            $this->repositoryFullName = $repository;
            $this->repositoryName = pathinfo( $rplc, PATHINFO_FILENAME );
        }
    }

    /**
     *
     */
    protected function createValidatorWithInterface()
    {
        $validatorInterfaceFullPath = $this->validatorBasePath.'/'.$this->validatorInterfaceName.'.php';
        if ( !$this->filesystem->exists( $validatorInterfaceFullPath ) )
        {
            // Read the stub and replace
            $this->makeDirectory( $this->validatorBasePath );
            $this->filesystem->put( $validatorInterfaceFullPath, $this->compileValidatorInterface() );
            $this->info("Validator interface created successfully for '$this->serviceClassName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The interface '$this->validatorInterfaceName' already exists, so it was skipped.");
        }

        // Now create the class.
        $validatorClassFullPath = $this->validatorClassPath.'/'.$this->validatorClassName.'.php';
        if ( !$this->filesystem->exists( $validatorClassFullPath ) )
        {
            $this->makeDirectory( $this->validatorClassPath );
            $this->filesystem->put( $validatorClassFullPath, $this->compileValidator() );
            $this->info("Validator created successfully for '$this->serviceClassName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The validator '$this->validatorClassName' already exists, so it was skipped.");
        }
    }

    /**
     *
     */
    protected function createServiceWithInterface()
    {
        $serviceInterfaceFullPath = $this->serviceBasePath.'/'.$this->serviceInterfaceName.'.php';
        if ( !$this->filesystem->exists( $serviceInterfaceFullPath ) )
        {
            // Read the stub and replace
            $this->makeDirectory( $this->serviceBasePath );
            $this->filesystem->put( $serviceInterfaceFullPath, $this->compileServiceInterface() );
            $this->info("Service interface created successfully for '$this->serviceClassName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The interface '$this->serviceInterfaceName' already exists, so it was skipped.");
        }

        $serviceClassFullPath = $this->serviceClassPath.'/'.$this->serviceClassName.'.php';
        if ( !$this->filesystem->exists( $serviceClassFullPath ) )
        {
            $this->makeDirectory( $this->serviceClassPath );
            $this->filesystem->put( $serviceClassFullPath, $this->compileService() );
            $this->info("Service '$this->serviceClassName' created successfully.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The service '$this->serviceClassName' already exists, so it was skipped.");
        }
    }

    /**
     *
     */
    protected function createServiceProvider()
    {
        $serviceProviderClassName = $this->getServiceProviderName();

        $classFilePath = $this->serviceBasePath.'/'.$serviceProviderClassName.'.php';

        if ( !$this->filesystem->exists( $classFilePath ) )
        {
            $this->makeDirectory( $this->serviceBasePath );
            $this->filesystem->put( $classFilePath, $this->compileServiceProvider() );
            $this->info("Service provider created successfully for '$this->serviceClassName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The service provider '$serviceProviderClassName' already exists, so it was skipped.");
        }
    }

    /**
     * @return mixed|string
     */
    protected function compileServiceInterface()
    {
        $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-interface.stub');

        $stub = str_replace('{{serviceInterfaceNamespace}}', $this->serviceNamespace, $stub);
        $stub = str_replace('{{serviceInterfaceName}}', $this->serviceInterfaceName, $stub);

        return $stub;
    }

    /**
     * @return mixed|string
     */
    protected function compileService()
    {
        if ( $this->repositoryName && !$this->validatorClassName )
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-w-repo.stub');
        } elseif ( $this->repositoryName && $this->validatorClassName  )
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-w-repo-validator.stub');
        } else if ( !$this->repositoryName && $this->validatorClassName )
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-w-validator.stub');
        } else
            {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service.stub');
        }

        $stub = str_replace('{{serviceInterfaceNamespace}}', $this->serviceNamespace, $stub);
        $stub = str_replace('{{serviceInterfaceName}}', $this->serviceInterfaceName, $stub);
        $stub = str_replace('{{serviceClassName}}', $this->serviceClassName, $stub);

        if ( $this->repositoryName )
        {
            // Load the values for repository.
            $stub = str_replace('{{repositoryInterfaceFullName}}', $this->repositoryFullName, $stub);
            $stub = str_replace('{{repositoryInterfaceName}}', $this->repositoryName, $stub);
            $stub = str_replace('{{repositoryName}}', lcfirst( str_replace( 'Interface', '', $this->repositoryName ) ), $stub);
        }

        if ( $this->validatorClassName )
        {
            // Load the values for repository.
            $stub = str_replace('{{validatorInterfaceNamespace}}', $this->validatorInterfaceNamespace, $stub);
            $stub = str_replace('{{validatorInterface}}', $this->validatorInterfaceName, $stub);
            $stub = str_replace('{{validatorName}}', lcfirst( str_replace( 'Interface', '', $this->validatorInterfaceName ) ), $stub);
        }

        return $stub;
    }

    /**
     * @return mixed|string
     */
    protected function compileServiceProvider()
    {
        if ( $this->validatorClassName )
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-provider-w-validator.stub');
        } else
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-provider.stub');
        }

        $serviceProviderClassName = $this->getServiceProviderName();

        $stub = str_replace('{{serviceProviderClassName}}', $serviceProviderClassName, $stub);
        $stub = str_replace('{{serviceInterfaceNamespace}}', $this->serviceNamespace, $stub);
        $stub = str_replace('{{serviceInterfaceName}}', $this->serviceInterfaceName, $stub);
        $stub = str_replace('{{serviceClassName}}', $this->serviceClassName, $stub);

        if ( $this->validatorClassName )
        {
            // Load the values for repository.
            $stub = str_replace('{{validatorInterfaceNamespace}}', $this->validatorInterfaceNamespace, $stub);
            $stub = str_replace('{{validatorInterface}}', $this->validatorInterfaceName, $stub);
            $stub = str_replace('{{validatorClass}}', $this->validatorClassName, $stub);
        }

        return $stub;
    }

    /**
     * @return mixed|string
     */
    protected function compileValidatorInterface()
    {
        $stub = $this->filesystem->get(__DIR__ . '/../stubs/validator-interface.stub');

        $stub = str_replace('{{validatorInterfaceNamespace}}', $this->validatorInterfaceNamespace, $stub);
        $stub = str_replace('{{validatorInterface}}', $this->validatorInterfaceName, $stub);

        return $stub;
    }

    /**
     * @return mixed|string
     */
    protected function compileValidator()
    {
        $stub = $this->filesystem->get(__DIR__ . '/../stubs/validator.stub');

        $stub = str_replace('{{validatorInterfaceNamespace}}', $this->validatorInterfaceNamespace, $stub);
        $stub = str_replace('{{validatorInterface}}', $this->validatorInterfaceName, $stub);
        $stub = str_replace('{{validatorClass}}', $this->validatorClassName, $stub);

        return $stub;
    }


    /**
     * @param $path
     */
    protected function makeDirectory( $path )
    {
        if ( !$this->filesystem->isDirectory( $path ) )
        {
            $this->filesystem->makeDirectory( $path, 0775, true, true);
        }
    }

    /**
     * @return string
     */
    public function getServiceProviderName()
    {
        return $this->serviceClassName.'ServiceProvider';
    }
}