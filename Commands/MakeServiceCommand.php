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

    protected $signature = 'make:service {service-group}';
    protected $description = 'Interactively create a new service.';
    protected $filesystem;
    private $composer;


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
        $serviceGroup = $this->argument('service-group');

        $serviceName = $this->ask('Please introduce the name of the service');
        $injectRepo = $this->confirm('Do you want to inject a repository for this validator?', true);
        $createAndInjectValidator = $this->confirm('Do you want to create a validator for this service?', true);

        if ( $injectRepo ) $repo = $this->ask('Please specify the model for which you want to inject the repository');
        else $repo = FALSE;

        // Create validator?
        if ( $createAndInjectValidator ) $validator = $this->createValidatorWithInterface( $serviceGroup, $serviceName );
        else $validator = FALSE;

        $this->createServiceWithInterface( $serviceGroup, $serviceName, $repo, $validator );

        $this->createServiceProvider( $serviceGroup, $serviceName, $validator );
    }

    /**
     * @param $serviceGroup
     * @param $serviceName
     * @return bool
     */
    protected function createValidatorWithInterface( $serviceGroup, $serviceName )
    {
        $validatorInterfaceNamespace = $this->getBaseValidatorNamespace( $serviceGroup );
        $validatorInterface = $this->getValidatorInterfaceName( $serviceName );

        $interfaceFilePath = config( 'service-generator.path' ).'/'.$serviceGroup.'/Validation/'.$validatorInterface.'.php';

        if ( !$this->filesystem->exists( $interfaceFilePath ) )
        {
            // Read the stub and replace
            $this->makeDirectory( dirname( $interfaceFilePath ) );
            $this->filesystem->put( $interfaceFilePath, $this->compileValidatorInterface( $validatorInterfaceNamespace, $validatorInterface ) );
            $this->info("Validator interface created successfully for '$serviceName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The interface '$validatorInterface' already exists, so it was skipped.");
        }

        // Now create the class.
        $validatorClassName = $this->getValidatorClassName( $serviceName );

        $classFilePath = config( 'service-generator.path' ).'/'.$serviceGroup.'/Validation/src/'.$validatorClassName.'.php';

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
     * @param $serviceGroup
     * @param $serviceName
     * @param null $withRepository
     * @param null $withValidator
     */
    protected function createServiceWithInterface( $serviceGroup, $serviceName, $withRepository = NULL, $withValidator = NULL )
    {
        $serviceInterfaceNamespace = $this->getBaseServiceNamespace( $serviceGroup );
        $serviceInterface = $this->getServiceInterfaceName( $serviceName );

        $interfaceFilePath = config( 'service-generator.path' ).'/'.$serviceGroup.'/'.$serviceInterface.'.php';

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

        $classFilePath = config( 'service-generator.path' ).'/'.$serviceGroup.'/src/'.$serviceClassName.'.php';

        if ( !$this->filesystem->exists( $classFilePath ) )
        {
            $this->makeDirectory( dirname( $classFilePath ) );
            $this->filesystem->put( $classFilePath, $this->compileService( $serviceGroup, $serviceName, $withRepository, $withValidator ) );
            $this->info("Service created successfully for '$serviceName'.");
            $this->composer->dumpAutoloads();
        } else
        {
            $this->error("The service '$serviceClassName' already exists, so it was skipped.");
        }
    }

    /**
     * @param $serviceGroup
     * @param $serviceName
     * @param null $withValidator
     */
    protected function createServiceProvider( $serviceGroup, $serviceName, $withValidator = NULL )
    {
        $serviceProviderClassName = $this->getServiceProviderName( $serviceGroup );

        $classFilePath = config( 'service-generator.path' ).'/'.$serviceGroup.'/'.$serviceProviderClassName.'.php';

        if ( !$this->filesystem->exists( $classFilePath ) )
        {
            $this->makeDirectory( dirname( $classFilePath ) );
            $this->filesystem->put( $classFilePath, $this->compileServiceProvider( $serviceGroup, $serviceName, $withValidator ) );
            $this->info("Service provider created successfully for '$serviceGroup'.");
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
     * @param $serviceGroup
     * @param $serviceName
     * @param $withValidator
     * @param $withRepository
     * @return mixed
     */
    protected function compileService( $serviceGroup, $serviceName, $withRepository, $withValidator )
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

        $serviceInterfaceNamespace = $this->getBaseServiceNamespace( $serviceGroup );
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
            $validatorInterfaceNamespace = $this->getBaseValidatorNamespace( $serviceGroup );
            $validatorInterface = $withValidator.'ValidatorInterface';
            $validatorName = strtolower( $withValidator ).'Validator';

            $stub = str_replace('{{validatorInterfaceNamespace}}', $validatorInterfaceNamespace, $stub);
            $stub = str_replace('{{validatorInterface}}', $validatorInterface, $stub);
            $stub = str_replace('{{validatorName}}', $validatorName, $stub);
        }

        return $stub;
    }

    /**
     * @param $serviceGroup
     * @param $serviceName
     * @param $withValidator
     * @return mixed
     */
    protected function compileServiceProvider( $serviceGroup, $serviceName, $withValidator )
    {
        if ( $withValidator )
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-provider-w-validator.stub');
        } else
        {
            $stub = $this->filesystem->get(__DIR__ . '/../stubs/service-provider.stub');
        }

        $serviceProviderClassName = $this->getServiceProviderName( $serviceGroup );
        $serviceInterfaceNamespace = $this->getBaseServiceNamespace( $serviceGroup );
        $serviceInterfaceName = $this->getServiceInterfaceName( $serviceName );
        $serviceClassName = $this->getServiceClassName( $serviceName );

        $stub = str_replace('{{serviceProviderClassName}}', $serviceProviderClassName, $stub);
        $stub = str_replace('{{serviceInterfaceNamespace}}', $serviceInterfaceNamespace, $stub);
        $stub = str_replace('{{serviceInterfaceName}}', $serviceInterfaceName, $stub);
        $stub = str_replace('{{serviceClassName}}', $serviceClassName, $stub);

        if ( $withValidator )
        {
            // Load the values for repository.
            $validatorInterfaceNamespace = $this->getBaseValidatorNamespace( $serviceGroup );
            $validatorInterface = $withValidator.'ValidatorInterface';
            $validatorName = $this->getValidatorClassName( $serviceName );

            $stub = str_replace('{{validatorInterfaceNamespace}}', $validatorInterfaceNamespace, $stub);
            $stub = str_replace('{{validatorInterface}}', $validatorInterface, $stub);
            $stub = str_replace('{{validatorClass}}', $validatorName, $stub);
        }

        return $stub;
    }





    /**
     * @param $validatorInterfaceNamespace
     * @param $validatorInterface
     * @return mixed
     */
    protected function compileValidatorInterface( $validatorInterfaceNamespace, $validatorInterface )
    {
        $stub = $this->filesystem->get(__DIR__ . '/../stubs/validator-interface.stub');

        $stub = str_replace('{{validatorInterfaceNamespace}}', $validatorInterfaceNamespace, $stub);
        $stub = str_replace('{{validatorInterface}}', $validatorInterface, $stub);

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
     * @param $serviceGroup
     * @return string
     */
    protected function getBaseServiceNamespace( $serviceGroup )
    {
        $baseNamespace = rtrim( config( 'service-generator.namespace' ), '\\' ) . '\\';

        return $baseNamespace.$serviceGroup;
    }

    /**
     * @param $serviceGroup
     * @return string
     */
    protected function getBaseValidatorNamespace( $serviceGroup )
    {
        $base = $this->getBaseServiceNamespace( $serviceGroup );

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
     * @param $serviceGroup
     * @return string
     */
    public function getServiceProviderName( $serviceGroup )
    {
        return $serviceGroup.'ServiceProvider';
    }
}