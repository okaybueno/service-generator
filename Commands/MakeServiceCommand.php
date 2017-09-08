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
    protected $repositoryNamespace;
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

            $repository = $this->confirm('Do you want to inject a repository for this validator?', false);
            $validator = $this->confirm('Do you want to create a validator for this service?', true);

            if ( $repository ) $repository = $this->ask('Please specify the full class name for the repository that you want to inject');
            else $repository = FALSE;

            $this->populateValuesForProperties( $service, $group, $repository, $validator );
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

        $this->serviceGroup = $group;
        $this->serviceBasePath = $groups[ $group ];
        $this->serviceNamespace = $group;
        $this->serviceInterfaceName = $service.'Interface';
        $this->serviceClassPath = rtrim($this->serviceBasePath, '/').'/src';
        $this->serviceClassName = $service;

        if ( $validator )
        {
            $this->validatorBasePath = rtrim($this->serviceBasePath, '/').'/Validation';
            $this->validatorInterfaceNamespace = $this->serviceNamespace.'/Validation';
            $this->validatorInterfaceName = $service.'ValidatorInterface';
            $this->validatorClassName = $service.'LaravelValidator';
        }

        if ( $repository )
        {
            $rplc = str_replace( '\\', '/', $repository );
            $this->repositoryNamespace = str_replace( '//', '/', pathinfo( $rplc, PATHINFO_DIRNAME ) );
            $this->repositoryName = pathinfo( $rplc, PATHINFO_FILENAME );
        }
    }

    /**
     * @return mixed
     */
    protected function createValidatorWithInterface()
    {
        if ( !$this->filesystem->exists( $this->validatorBasePath.'/'.$this->validatorInterfaceName ) )
        {
            // Read the stub and replace
            $this->makeDirectory( $this->validatorBasePath );
            $this->filesystem->put( $this->validatorBasePath.'/'.$this->validatorInterfaceName.'.php', $this->compileValidatorInterface( ) );
            $this->info("Validator interface created successfully for '$this->serviceClassName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The interface '$this->validatorInterfaceName' already exists, so it was skipped.");
        }

        // Now create the class.
        $validatorClassName = $this->getValidatorClassName( $serviceName );

        $classFilePath = config( 'service-generator.path' ).'/'.$service.'/Validation/src/'.$validatorClassName.'.php';

        if ( !$this->filesystem->exists( $classFilePath ) )
        {
            $this->makeDirectory( dirname( $classFilePath ) );
            $this->filesystem->put( $classFilePath, $this->compileValidator( $validatorInterfaceNamespace, $validatorInterface, $validatorClassName ) );
            $this->info("Validator created successfully for '$serviceName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The validator '$validatorClassName' already exists, so it was skipped.");
        }

        return $serviceName;
    }

    /**
     * @param $service
     * @param $serviceName
     * @param null $withRepository
     * @param null $withValidator
     */
    protected function createServiceWithInterface( $service, $serviceName, $withRepository = NULL, $withValidator = NULL )
    {
        $serviceInterfaceNamespace = $this->getBaseServiceNamespace( $service );
        $serviceInterface = $this->getServiceInterfaceName( $serviceName );

        $interfaceFilePath = config( 'service-generator.path' ).'/'.$service.'/'.$serviceInterface.'.php';

        if ( !$this->filesystem->exists( $interfaceFilePath ) )
        {
            // Read the stub and replace
            $this->makeDirectory( dirname( $interfaceFilePath ) );
            $this->filesystem->put( $interfaceFilePath, $this->compileServiceInterface( $serviceInterfaceNamespace, $serviceInterface ) );
            $this->info("Service interface created successfully for '$serviceName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The interface '$serviceInterface' already exists, so it was skipped.");
        }

        $serviceClassName = $this->getServiceClassName( $serviceName );

        $classFilePath = config( 'service-generator.path' ).'/'.$service.'/src/'.$serviceClassName.'.php';

        if ( !$this->filesystem->exists( $classFilePath ) )
        {
            $this->makeDirectory( dirname( $classFilePath ) );
            $this->filesystem->put( $classFilePath, $this->compileService( $service, $serviceName, $withRepository, $withValidator ) );
            $this->info("Service created successfully for '$serviceName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The service '$serviceClassName' already exists, so it was skipped.");
        }
    }

    /**
     * @param $service
     * @param $serviceName
     * @param null $withValidator
     */
    protected function createServiceProvider( $service, $serviceName, $withValidator = NULL )
    {
        $serviceProviderClassName = $this->getServiceProviderName( $service );

        $classFilePath = config( 'service-generator.path' ).'/'.$service.'/'.$serviceProviderClassName.'.php';

        if ( !$this->filesystem->exists( $classFilePath ) )
        {
            $this->makeDirectory( dirname( $classFilePath ) );
            $this->filesystem->put( $classFilePath, $this->compileServiceProvider( $service, $serviceName, $withValidator ) );
            $this->info("Service provider created successfully for '$service'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The service provider '$serviceProviderClassName' already exists, so it was skipped.");
        }
    }

    /**
     * @param $serviceInterfaceNamespace
     * @param $serviceInterfaceName
     * @return mixed
     */
    protected function compileServiceInterface( $serviceInterfaceNamespace, $serviceInterfaceName )
    {
        $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-interface.stub');

        $stub = str_replace('{{serviceInterfaceNamespace}}', $serviceInterfaceNamespace, $stub);
        $stub = str_replace('{{serviceInterfaceName}}', $serviceInterfaceName, $stub);

        return $stub;
    }

    /**
     * @param $service
     * @param $serviceName
     * @param $withValidator
     * @param $withRepository
     * @return mixed
     */
    protected function compileService( $service, $serviceName, $withRepository, $withValidator )
    {
        if ( $withRepository && !$withValidator )
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-w-repo.stub');
        } elseif ( $withRepository && $withValidator )
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-w-repo-validator.stub');
        } else
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service.stub');
        }

        $serviceInterfaceNamespace = $this->getBaseServiceNamespace( $service );
        $serviceInterfaceName = $this->getServiceInterfaceName( $serviceName );
        $serviceClassName = $this->getServiceClassName( $serviceName );

        $stub = str_replace('{{serviceInterfaceNamespace}}', $serviceInterfaceNamespace, $stub);
        $stub = str_replace('{{serviceInterfaceName}}', $serviceInterfaceName, $stub);
        $stub = str_replace('{{serviceClassName}}', $serviceClassName, $stub);

        if ( $withRepository )
        {
            // Load the values for repository.
            $repositoryNamespace = config('service-generator.repositories_namespace');
            $repositoryInterfaceName = $withRepository.'RepositoryInterface';
            $repositoryName = strtolower( $withRepository ).'Repository';

            $stub = str_replace('{{repositoryInterfaceNamespace}}', $repositoryNamespace, $stub);
            $stub = str_replace('{{repositoryInterfaceName}}', $repositoryInterfaceName, $stub);
            $stub = str_replace('{{repositoryName}}', $repositoryName, $stub);
        }

        if ( $withValidator )
        {
            // Load the values for repository.
            $validatorInterfaceNamespace = $this->getBaseValidatorNamespace( $service );
            $validatorInterface = $withValidator.'ValidatorInterface';
            $validatorName = strtolower( $withValidator ).'Validator';

            $stub = str_replace('{{validatorInterfaceNamespace}}', $validatorInterfaceNamespace, $stub);
            $stub = str_replace('{{validatorInterface}}', $validatorInterface, $stub);
            $stub = str_replace('{{validatorName}}', $validatorName, $stub);
        }

        return $stub;
    }

    /**
     * @param $service
     * @param $serviceName
     * @param $withValidator
     * @return mixed
     */
    protected function compileServiceProvider( $service, $serviceName, $withValidator )
    {
        if ( $withValidator )
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-provider-w-validator.stub');
        } else
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-provider.stub');
        }

        $serviceProviderClassName = $this->getServiceProviderName( $service );
        $serviceInterfaceNamespace = $this->getBaseServiceNamespace( $service );
        $serviceInterfaceName = $this->getServiceInterfaceName( $serviceName );
        $serviceClassName = $this->getServiceClassName( $serviceName );

        $stub = str_replace('{{serviceProviderClassName}}', $serviceProviderClassName, $stub);
        $stub = str_replace('{{serviceInterfaceNamespace}}', $serviceInterfaceNamespace, $stub);
        $stub = str_replace('{{serviceInterfaceName}}', $serviceInterfaceName, $stub);
        $stub = str_replace('{{serviceClassName}}', $serviceClassName, $stub);

        if ( $withValidator )
        {
            // Load the values for repository.
            $validatorInterfaceNamespace = $this->getBaseValidatorNamespace( $service );
            $validatorInterface = $withValidator.'ValidatorInterface';
            $validatorName = $this->getValidatorClassName( $serviceName );

            $stub = str_replace('{{validatorInterfaceNamespace}}', $validatorInterfaceNamespace, $stub);
            $stub = str_replace('{{validatorInterface}}', $validatorInterface, $stub);
            $stub = str_replace('{{validatorClass}}', $validatorName, $stub);
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
     * @param $validatorInterfaceNamespace
     * @param $validatorInterface
     * @param $validatorClass
     * @return mixed
     */
    protected function compileValidator( $validatorInterfaceNamespace, $validatorInterface, $validatorClass )
    {
        $stub = $this->filesystem->get(__DIR__ . '/../stubs/validator.stub');

        $stub = str_replace('{{validatorInterfaceNamespace}}', $validatorInterfaceNamespace, $stub);
        $stub = str_replace('{{validatorInterface}}', $validatorInterface, $stub);
        $stub = str_replace('{{validatorClass}}', $validatorClass, $stub);

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
     * @param $service
     * @return string
     */
    protected function getBaseServiceNamespace( $service )
    {
        $baseNamespace = rtrim( config( 'service-generator.namespace' ), '\\' ) . '\\';

        return $baseNamespace.$service;
    }

    /**
     * @param $service
     * @return string
     */
    protected function getBaseValidatorNamespace( $service )
    {
        $base = $this->getBaseServiceNamespace( $service );

        return $base.'\Validation';
    }

    /**
     * @param $serviceName
     * @return string
     */
    protected function getValidatorInterfaceName( $serviceName )
    {
        return $serviceName.'ValidatorInterface';
    }

    /**
     * @param $serviceName
     * @return string
     */
    protected function getValidatorClassName( $serviceName )
    {
        return  $serviceName.'ValidatorLaravel';
    }

    /**
     * @param $serviceName
     * @return string
     */
    public function getServiceInterfaceName( $serviceName )
    {
        return $serviceName.'ServiceInterface';
    }

    /**
     * @param $serviceName
     * @return string
     */
    public function getServiceClassName( $serviceName )
    {
        return $serviceName.'Service';
    }

    /**
     * @param $service
     * @return string
     */
    public function getServiceProviderName( $service )
    {
        return $service.'ServiceProvider';
    }
}