<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\ImportCsvData as ImportCsvDataEvent;
use App\Module;
use Notification;
use App\Notifications\CsvImport;

class ImportCsvData implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ImportCsvDataEvent $event)
    {      
        foreach($event as $csvData) {         
            foreach($csvData as $importData) {   
                $data['module_code'] = $importData['csvData']['module_code']; 
                $data['module_name'] = $importData['csvData']['module_name']; 
                $data['module_term'] = $importData['csvData']['module_term']; 
                Module::create($data);
            } 
                      
        }
        $mail = config('mail.adminAddress');
        Notification::route('mail', $mail)->notify(new CsvImport());
    }
}
