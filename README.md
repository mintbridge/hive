# Hive^alpha

Hive is a modeling system for the [Kohana Framework](http://kohanaframework.org/). The goals of Hive are:

1. Maintain proper typing when working with records.
2. Track changes to the model state and values.
3. Validate model data, based on user defined rules.
4. Create a model of any database record or aggregate query.
5. Provide basic CRUD support with minimal effort.

_Hive requires Kohana 3.0.x and PHP 5.3.x. The API will be in flux until 1.0 release!_

## Show me a model!

All models must declare an `init` method. This method must obtain the meta object by calling `parent::init` and return the meta object. The meta object must have the `table` and `fields` properties filled in to function properly. All other meta properties are optional.

    class Model_User extends Hive {

        public static function init()
        {
            $meta = parent::init();

            $meta->table = 'users';

            $meta->fields += array(
                'id' => new Hive_Field_Auto,
                'email' => new Hive_Field_Email(array(
                    'unique' => TRUE,
                )),
                'password'  => new Hive_Field_String,
                'created' => new Hive_Field_Timestamp,
                'updated' => new Hive_Field_Timestamp,
                'last_login' => new Hive_Field_Timestamp,
            );

            $meta->sorting['id'] = 'asc';

            $meta->rules += array(
                'email' => array(
                    'not_empty'  => NULL,
                    'max_length' => array(127),
                    'email'      => NULL,
                ),
                'password' => array(
                    'min_length' => array(5),
                ),
            );

            return $meta;
        }

    } // End User

## Get that CRUD out of the way!

### Fabricate

| ˈfabrəˌkāt | __verb__ construct or manufacture (something, esp. an industrial product), esp. from prepared components /  __origin__ from Latin _fabrica_ "something skillfully produced".

    // This could come from any array, such as $_POST
    $data = array(
        'email' => 'john.doe@example.com',
        'password' => 'johnISme',
    );

    $user = Hive::factory('user', $data);

    try
    {
        $user->create();
    }
    catch (Kohana_Validate_Exception $e)
    {
        // Get the validation errors
        $errors = $e->array->errors();

        echo Kohana::debug('could not create user:', $errors);
        exit;
    }

    echo Kohana::debug("created new user #{$user->id}", $user->as_array());

### Collate

| ˈkōˌlāt | __verb__ collect and combine (texts, information, or sets of figures) in proper order / __origin__ from Latin _collat_ "brought together"

    $user = Hive::factory('user', array(
        'email' => 'john.doe@example.com',
    ));

    // The model is not loaded yet
    echo Kohana::debug($user->loaded()); // FALSE

    // But a unique field (email) has been set, the model is prepared for loading
    echo Kohana::debug($user->prepared()); // TRUE

    // This will trigger auto-loading
    echo Kohana::debug($user->email);

    // Or you can do it manually
    $user->read();

    // Want to find the most recently created user? Pass a query.
    $query = DB::select()->order_by('created', 'desc');
    $user = Hive::factory('user')
        ->read($query);

    // How about a database result of all the users? Easy.
    $users = Hive::factory('user')->read(NULL, FALSE);

### Ameliorate

| əˈmēlyəˌrāt | __verb__ make (something bad or unsatisfactory) better / __origin__ alteration of _meliorate_, influenced by French _améliorer_, from _meilleur_ "better"

    $user = Hive::factory('user', array(
        'id' => 1,
    ));

    $user->values(array(
        'password' => 'johnBEstrong',
    ));

    try
    {
        $user->update();
    }
    catch (Kohana_Validate_Exception $e)
    {
        // Get the validation errors
        $errors = $e->array->errors();

        echo Kohana::debug('could not update user:', $errors);
        exit;
    }

    echo Kohana::debug($user->password);

### Eradicate

| iˈradiˌkāt | __verb__ destroy completely; put an end to / __origin__ from Latin _eradicat_ "torn up by the roots"

    // Want a single liner?
    Hive::factory('user', array('id' => 1))->delete();

    // Delete all users that have not logged in for over a year.
    $query = DB::delete()
        ->where('last_login', '<', strtotime('-1 year'));

    Hive::factory('user')->delete($query, FALSE);

## Interesting Stuff

Hive will get more interesting with time. This section will be updated as other cool things are possible.

### Aliases

You can do is create aliases, anonymous functions that return additional model information as strings:

    $meta->aliases += array(
        'full_name' => function($model)
        {
            return trim("{$model->first_name} {$model->last_name}");
        },
    );

All aliases get one parameter, the current model.
