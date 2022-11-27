# sabo-framework

mini php framework require php 8

to start a project with

  mkdir my_project

  cd my_project

  git clone https://github.com/yahvya/sabo-framework .

  sudo rm -r .git 
  
  git init

SABO

come with a small cli tool named sabo 

php bin/sabo --help to list commands

add customized commands to sabo step

  - create your classes in bin/utils
  - the class have to extends AbstractCommand and implements
    - public static function exec_command(int $argc, array $argv, string $project_root_path):void which will be called to execute your command
    - redefine public static function print_args():void to print your own arguments
  - finally add in bin/sabo file
  - use \Sabo\YourClass;
  - AbstractCommand::add_command("your_command_name",YourClass::class,"your command description");
  
you can customize the make:controller controller format by updating bin/resources/controller_model.txt same for the make:model
 
Views

- views are provided with twig and are locates in views/templates/

- to move this folder you have to redefine $this->view_start_path in your controller or directly in abstract controller

- you can add your own extensions and filters to twig in the AbstractController or in your controller with $this->get_twig_environment()->addExtension(instance_off_your_class);

Routes and Assets in view

- two functions are added by default 
    
    - route() and asset() throws exceptions in debug_mode
    
    - route take the name of the route and if it's a genereic you can pass a twig array to replace generic elements 
        example: route('home_page') or route('view_an_article',{'article_id' : 1})
        
    - css and js files are allowed in public and views folders , asset function take the type (css or js) and the file name , it will search the file in:
        - your_twig_file_directory/css for a css type or your_twig_file_directory/js for a js type
        
        - if not found it will search in public/css for a css type or public/js for a js file 
        
          example: this->render("article/article.twig"); the search will start in views/templates/article/css/ or views/templates/articles/js/

controllers extends route function too with $this->route("route_name",@optionnal["name" => "replace"]);

Controller

you can create a controller using php ./bin/sabo make:controller controller_name and customize the default controller model by changing bin/resources/controller_model.txt

a controller have to extends AbstractController

if you redefine the construct -> __construct($routes_names,$debug_mode) be sure to past params to the parent using parent::__construct($routes_names,$debug_mode);

render to render your view (you can past data to twig with the second optionnal parameter)

redirect with take a route to redirect (can be combine with routes to get a route -> $this->redirect($this->route("route_name")); )


Models

you can create a model using php ./bin/sabo make:model model_name and customize the default model model by changing bin/resources/model_model.txt

models support mysql you have to use INNODB stockage engine to use models transaction

a model have to
  
  - extends AbstractModel
  
  - use the TableName attribute to define the linked table name in database (already do if you use sabo)
  
  - implements the abstract method get_object_from_row which have to return this model instance with a row from database
  
a model property have to

  - use the TableColumn attribute if linked to the table column and be public/protected to describe the column
  - to put conditions when a linked property is going to be set, use the attribute ColumnCond (RegexCond is available but you can create your own Cond by implementing the CondInterface)
  
  Env file 
  
  the env file have to 
  
    - be .env or a env.json located in config folder
  
    - contain the maintenance state of the site (maintenance=true)
    - database datas
    
 the default configuration use env.json
 
 to use .env modify in index.php
 
 define("CONFIG_FILE_TYPE",Router::JSON_ENV) to define("CONFIG_FILE_TYPE",Router::CLASSIC_ENV);
 
 Serialize models
 
 to serialize a model you have to use $model->get_serialized_version(); which will return the serialized version of your model after erasing unserializable elements (for example PDO and the columns you mark to erase before with serialize with the TableColumn attribute), your base model object will not change after 
 
 to unserialize a model use the static method AbstractModel::unserialize_model you can add an array to replace some erased values ["class_property_name" => "value_to_set"]
 
 Include private js
 
 via .htaccess, js files which are located in views/templates/*/administrator/.js are not accessible with url
 
 to include these js files in your view you have to set the third optionnal parameter of asset function to true -> {{ asset("js","your_js_file",true) }}  
 
 
 
 
