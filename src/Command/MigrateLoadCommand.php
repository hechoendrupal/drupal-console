<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\MigrateLoadCommand.
 */

namespace Drupal\AppConsole\Command;

 
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\AppConsole\Command\MigrateDebugCommand;

class MigrateLoadCommand extends ContainerAwareCommand
{
     protected $invalid_file;

     protected function configure()
    {
        $this
          ->setName('migrate:load')
          ->setDescription($this->trans('commands.migrate.load.description'))
          ->addOption('override', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.migrate.load.questions.override'))
          ->addArgument('file', InputArgument::OPTIONAL, $this->trans('commands.migrate.load.arguments.file'));
          
        $this->addDependency('migrate');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        
        
        $validator_required = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception(' You must provide a valid file path and name.');
            }
            return $value;
        };

       
        $file = $input->getArgument('file');

        if (!$file) {
            $dialog = $this->getDialogHelper();
            $file = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.migrate.load.questions.file'),
                ''),
              $validator_required,
              false,
              ''
            );
        }
        $input->setArgument('file', $file);

        $file_data = $this->loadDataFile($file);
        $migration_id = $this->validateMigration($file_data['migration_groups']['0'],$file_data['id']);
       
        $override = $input->getOption('override');

        if($migration_id == true){
         $override_required = function ($value) {
            if (!strlen(trim($value))) {
                throw new \Exception(' Please provide an answer');
            }
            return $value;
        };

          $dialog = $this->getDialogHelper();
          $override = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.migrate.load.questions.override'),
                ''),
              $override_required,
              false,
              ''
            );
          
        }
        $input->setOption('override', $override);

    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $this->getHelperSet()->get('message');
        $file = null;
        if ($input->hasArgument('file')) {
            $file = $input->getArgument('file');
        }
         
        if (!file_exists($file)) {
            $message = $this->getHelperSet()->get('message');
            $message->addErrorMessage(
              sprintf(
                $this->trans('commands.migrate.load.messages.invalid_file'),
                $file
              )
            );
            return 1;
        }
      
        try {

         $file_data = $this->loadDataFile($file);
         $entity_manager = $this->getEntityManager();
         $entity_storage = $entity_manager->getStorage('migration');
         $id_found = $this->validateMigration($file_data['migration_groups']['0'],$file_data['id']);
         
         if ($id_found == false) {
            $migration_entity = $entity_storage->createFromStorageRecord($file_data); 

           if ($migration_entity->isInstallable()) {
            $migration_entity->trustData()->save();
            $output->writeln('[+] <info>' . sprintf($this->trans('commands.migrate.load.messages.installed') . '</info>'));
           } 

         }

           $override = $input->getOption('override');

           if($override === 'yes'){
            $entity = $entity_storage->load($file_data['id']);
            $migration_updated = $entity_storage->updateFromStorageRecord($entity, $file_data);
            $migration_updated->trustData()->save();
            $output->writeln('[+] <info>' . sprintf($this->trans('commands.migrate.load.messages.overridden') . '</info>'));
            return;
         }
          else
          {
            return;
          } 
          
        } catch (Exception $e) {
          $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
            return;  
         }     
       
    }

    protected function validateMigration($drupal_version,$migrate_id){
       $migration_id_found = false;
       $migrations = $this->getMigrations($drupal_version);
       foreach ($migrations as $migration_id => $migration) {
           if (strcmp($migration_id, $migrate_id) == 0) {
                  $migration_id_found = true;
                  break;
              }
        }
       return $migration_id_found;
    }


    protected function loadDataFile($file){
       $yml = new Parser();
       $file_data = $yml->parse(file_get_contents($file));
       return $file_data;

    }


     
}
