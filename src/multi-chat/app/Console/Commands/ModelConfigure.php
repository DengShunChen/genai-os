<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LLMs;
use App\Models\Permissions;
use App\Models\GroupPermissions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DB;

class ModelConfigure extends Command
{
    protected $signature = 'model:config {access_code} {name} {--image=}';
    protected $description = 'Quickly configure a model for everyone';
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $accessCode = $this->argument('access_code');
        $name = $this->argument('name');

        try {
            if (LLMs::where('access_code', '=', $accessCode)->exists()) {
                $this->error('The access code already exists! Aborted.');
            } elseif (LLMs::where('name', '=', $name)->exists()) {
                $this->error('The name already exists! Aborted.');
            } else {
                DB::beginTransaction(); // Start a database transaction
                $path = null;
                if ($this->option('image')) {
                    $imagePath = $this->option('image');
                    $fileContents = file_get_contents($imagePath);
                    $imageName = Str::random(40) . '.' . pathinfo($imagePath, PATHINFO_EXTENSION);
                    $path = 'public/images/' . $imageName;
                    Storage::put($path, $fileContents);
                }
                
                $model = new LLMs();
                $model->fill(['name' => $name, 'access_code' => $accessCode, "image"=>$path]);
                $model->save();
                $perm = new Permissions();
                $perm->fill(['name' => 'model_' . $model->id]);
                $perm->save();
                $currentTimestamp = now();

                $groups = GroupPermissions::pluck('group_id')->toArray();

                foreach ($groups as $group) {
                    GroupPermissions::where('group_id', $group)
                        ->where('perm_id', '=', $perm->id)
                        ->delete();
                    GroupPermissions::insert([
                        'group_id' => $group,
                        'perm_id' => $perm->id,
                        'created_at' => $currentTimestamp,
                        'updated_at' => $currentTimestamp,
                    ]);
                }
                DB::commit();
                $this->info('Model ' . $name . ' with access_code ' . $accessCode . ' configured successfully!');
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction in case of an exception
            throw $e; // Re-throw the exception to halt the migration
        }
    }
}
