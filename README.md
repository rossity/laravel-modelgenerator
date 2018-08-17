# laravel-modelgenerator
Easily scaffold an API model.

This is highly specific to my own use-cases and projects.

## Commands
`php artisan generate:template {model}`

 - Generates a template to `App\Http\Templates` that is customized and then called in the next command

`php artisan generate:model {model}`

 - Looks for the template file corresponding to the Model name from the first command

## Usage and Example
`php artisan generate:template Comment`

Configure the file based on configuration specs

     /**
      * The fields associated with the model
      *
      * @var array
      */
      public $fields = [
          'field_string' => [         // The name of the field
              'type' => 'string',     // In Migration and Request
              'default' => null,      // In Migration
              'required' => false,    // In Request
              'nullable' => true,     // In Migration and Request
              'extra_rules' => '',    // Validation rules other than 'nullable|required|type'
              'filterable' => true,   // Filterable via QueryBuilder, one of true|false|'exact'
          ],
      ];

      /**
       * Model that this model belongs to
       * Options below pertain only to the parent model
       * Used to create a relationship in the parent model and current model
       *
       * @var array
       */
      public $related = [
          'name' => 'Post
          'type' => 'hasMany' 
      ];

      /**
       * Log activity of this model using spatie/laravel-activitylog
       *
       * @var boolean
       */
      public $logActivity = true;

      /**
       * Add media to this model using spatie/laravel-medialibrary
       *
       * @var boolean
       */
      public $addMedia = false;

`php artisan generate:model Comment`
  
  Generates the following:
  
    - Model in `app\Http`
    - Resource Controller in `app\Http\Controllers\Api`
    - Policy in `App\Http\Policies` and registers it in `App\Providers\AuthServiceProvider`
    - Resource and Collection in `app\Http\Resources`
    - Request in `app\Http\Requests`
    - Factory in `database\factories`
    - Migration in `database\migrations`
    - Appends a resource route to `routes\api.php` or adds it to apiResource array
