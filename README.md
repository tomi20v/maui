Maui is a Minimal MongoDB mapper for PHP
Note this is a hobby project and is not and is not meant to be complete.
Originally just a weekend project, now I have some days of work in it.

The basic idea is to provide a framework:
 - define objects by schema, including their attributes and relations
 - easily create and access data and relatives
 - basic CRUD (hard-coded MongoDB) for nested objects, including deep saves of
    inline and referred data
 - basic validation
 - be extensible for sub-projects

Files, folders:
 app - sample app pieces
 bootstrap.php - just include this
 composer.json - just to include echop libs
 copying - see copyright notice
 ninja - some sketch, to be removed
 maui - maui lib
 README.md - reading this
 tests - some sporadic tests
 vendor/tomi20v/echop - pretty printer. Not necessary indeed

examples:

    $Video = new \Video(array('title' => 'First movie object'));
    $Video->load();

    echop($Video->title);

    echop($Video->getData(\ModelManager::DATA_ALL);

will produce something like:

    string(18) First movie object

    Video extends Maui\Model (
        const [REFERRED] => auto
        static [_schema:protected] => NULL
        [_originalData:protected] => Array(6) (
            [_id] => MongoId Object (
                [$id:public] => string(24) 53eeb9b526e34be00595d011
            )
            [user] => string(10) ArcheAdmin
            [title] => string(18) First movie object
            [subtitle] => string(19) First subtitle ever
            [length] => string(2) 54
            [staff] => Array(2) (
                [0] => Array(1) (
                    [_id] => MongoId Object (
                        [$id:public] => string(24) 53f27f9126e34bb1049f5d4c
                    )
                )
                [1] => Array(1) (
                    [name] => string(14) Steven McQueen
                )
            )
        )
        [_data:protected] => Array()
        [_isValidated:protected] => Array()
        [_validationErrors:protected] => Array()
        [_label:protected] => NULL
    )

now, continue code:

    $Staff1 = $Video->staff->at(1);
    $Staff1->name = 'Steve McQueen';

    echop($Video);

you get something like this:

    Video extends Maui\Model (
        const [REFERRED] => auto
        static [_schema:protected] => NULL
        [_originalData:protected] => Array(6) (
            [_id] => MongoId Object (
                [$id:public] => string(24) 53eeb9b526e34be00595d011
            )
            [user] => string(10) ArcheAdmin
            [title] => string(18) First movie object
            [subtitle] => string(19) First subtitle ever
            [length] => string(2) 54
            [staff] => Array(2) (
                [0] => Array(1) (
                    [_id] => MongoId Object (
                        [$id:public] => string(24) 53f27f9126e34bb1049f5d4c
                    )
                )
                [1] => Array(1) (
                    [name] => string(14) Steven McQueen
                )
            )
        )
        [_data:protected] => Array(1) (
            [staff] => Maui\Collection Object (
                [_modelClassname:protected] => string(5) Staff
                [_data:protected] => Array(3) (
                    [0] => Array(1) (
                        [_id] => MongoId Object (
                            [$id:public] => string(24) 53f27f9126e34bb1049f5d4c
                        )
                    )
                    [1] => Staff extends User (
                        const [REFERRED] => auto
                        static [_schema:protected] => NULL
                        static [_referred:protected] => string(6) inline
                        [_originalData:protected] => Array(1) (
                            [name] => string(14) Steven McQueen
                        )
                        [_data:protected] => Array(1) (
                            [name] => string(13) Steve McQueen
                        )
                        [_isValidated:protected] => Array()
                        [_validationErrors:protected] => Array()
                        [_label:protected] => NULL
                    )
                    [2] => Array(1) (
                        [name] => string(6) Smokey
                    )
                )
                [_filters:protected] => Array()
            )
        )
        [_isValidated:protected] => Array()
        [_validationErrors:protected] => Array()
        [_label:protected] => NULL
    )

note how the 'staff' value of $Video->_data was converted to a collection
 object, and it's elements also on access. Relation objects can exist,
 created by, or referred by as object, data, or just ID.

this is how the Video object is defined:

    class Video extends \Model {

        protected static $_schema = array(
            'user' => array(
                'class' => 'User',
                'referredField' => 'name',
                'reference' => \SchemaManager::REF_REFERENCE,
                'hasMin' => 1,
                'hasMax' => 1,
            ),
            'category' => 'Category',
            'rating' => array(
                'toString',
                'in' => array('G','PG','PG-13','R','NC-17'),
            ),
            'title' => array(
                'toString',
                'minLength' => 5,
                'maxLength' => 30,
            ),
            'subtitle' => array(
                'required',
                'maxLength' => 50,
            ),
            'description',
            'length' => array(
                'label' => 'Play time',
                'toInt',
                'min' => 1,
                'max' => 600,
            ),
            'director' => array(
                'class' => 'Staff',
                'reference' => \SchemaManager::REF_REFERENCE,
            ),
            'staff' => array(
                'label' => 'Cast',
                'class' => 'Staff',
                'reference' => \SchemaManager::REF_INLINE, // defined as inline as REF_AUTO is to be implemented
                'hasMin' => 0,
                'hasMax' => 5,
                'schema' => array(
                    'name',
                ),
            )
        );
    }

and this is something you'd get by printing the schema of Video object, after
 inflating (happens all automaticly):

    Maui\Schema Object (
        [_schema:protected] => Array(10) (
            [_id] => Maui\SchemaAttr Object (
                [_key:protected] => string(3) _id
                [_required:protected] => bool(false)
                [_validators:protected] => Array(1) (
                    [0] => Maui\SchemaValidatorToId extends Maui\SchemaValidatorTo (
                        const [FORMAT] => /^[0-9a-f]{24}$/
                        [_value:protected] => string(4) toId
                        [_parent:protected] => *RECURSION*
                    )
                )
                [_label:protected] => string(2) ID
            )
            [user] => Maui\SchemaRelative Object (
                [_key:protected] => string(4) user
                [_class:protected] => string(4) User
                [_reference:protected] => string(9) reference
                [_referredField:protected] => string(4) name
                [_schema:protected] => NULL
                [_validators:protected] => Array()
                [_hasMin:protected] => string(1) 1
                [_hasMax:protected] => string(1) 1
                [_label:protected] => NULL
            )
            [category] => Maui\SchemaRelative Object (
                [_key:protected] => string(8) category
                [_class:protected] => string(8) Category
                [_reference:protected] => string(6) inline
                [_referredField:protected] => string(3) _id
                [_schema:protected] => NULL
                [_validators:protected] => Array()
                [_hasMin:protected] => NULL
                [_hasMax:protected] => NULL
                [_label:protected] => NULL
            )
            [rating] => Maui\SchemaAttr Object (
                [_key:protected] => string(6) rating
                [_required:protected] => bool(false)
                [_validators:protected] => Array(2) (
                    [0] => Maui\SchemaValidatorToString extends Maui\SchemaValidatorTo (
                        [_value:protected] => string(8) toString
                        [_parent:protected] => *RECURSION*
                    )
                    [1] => Maui\SchemaValidatorIn extends Maui\SchemaValidator (
                        [_value:protected] => Array(5) (
                            [0] => string(1) G
                            [1] => string(2) PG
                            [2] => string(5) PG-13
                            [3] => string(1) R
                            [4] => string(5) NC-17
                        )
                        [_parent:protected] => *RECURSION*
                    )
                )
                [_label:protected] => NULL
            )
            [title] => Maui\SchemaAttr Object (
                [_key:protected] => string(5) title
                [_required:protected] => bool(false)
                [_validators:protected] => Array(3) (
                    [0] => Maui\SchemaValidatorToString extends Maui\SchemaValidatorTo (
                        [_value:protected] => string(8) toString
                        [_parent:protected] => *RECURSION*
                    )
                    [1] => Maui\SchemaValidatorMinLength extends Maui\SchemaValidator (
                        [_value:protected] => string(1) 5
                        [_parent:protected] => *RECURSION*
                    )
                    [2] => Maui\SchemaValidatorMaxLength extends Maui\SchemaValidator (
                        [_value:protected] => string(2) 30
                        [_parent:protected] => *RECURSION*
                    )
                )
                [_label:protected] => NULL
            )
            [subtitle] => Maui\SchemaAttr Object (
                [_key:protected] => string(8) subtitle
                [_required:protected] => bool(true)
                [_validators:protected] => Array(1) (
                    [0] => Maui\SchemaValidatorMaxLength extends Maui\SchemaValidator (
                        [_value:protected] => string(2) 50
                        [_parent:protected] => *RECURSION*
                    )
                )
                [_label:protected] => NULL
            )
            [description] => Maui\SchemaAttr Object (
                [_key:protected] => string(11) description
                [_required:protected] => bool(false)
                [_validators:protected] => Array()
                [_label:protected] => NULL
            )
            [length] => Maui\SchemaAttr Object (
                [_key:protected] => string(6) length
                [_required:protected] => bool(false)
                [_validators:protected] => Array(3) (
                    [0] => Maui\SchemaValidatorToInt extends Maui\SchemaValidatorTo (
                        [_value:protected] => string(5) toInt
                        [_parent:protected] => *RECURSION*
                    )
                    [1] => Maui\SchemaValidatorMin extends Maui\SchemaValidator (
                        [_value:protected] => string(1) 1
                        [_parent:protected] => *RECURSION*
                    )
                    [2] => Maui\SchemaValidatorMax extends Maui\SchemaValidator (
                        [_value:protected] => string(3) 600
                        [_parent:protected] => *RECURSION*
                    )
                )
                [_label:protected] => string(9) Play time
            )
            [director] => Maui\SchemaRelative Object (
                [_key:protected] => string(8) director
                [_class:protected] => string(5) Staff
                [_reference:protected] => string(9) reference
                [_referredField:protected] => string(3) _id
                [_schema:protected] => NULL
                [_validators:protected] => Array()
                [_hasMin:protected] => NULL
                [_hasMax:protected] => NULL
                [_label:protected] => NULL
            )
            [staff] => Maui\SchemaRelative Object (
                [_key:protected] => string(5) staff
                [_class:protected] => string(5) Staff
                [_reference:protected] => string(6) inline
                [_referredField:protected] => string(3) _id
                [_schema:protected] => Maui\Schema Object (
                    [_schema:protected] => Array(1) (
                        [name] => Maui\SchemaAttr Object (
                            [_key:protected] => string(4) name
                            [_required:protected] => bool(false)
                            [_validators:protected] => Array()
                            [_label:protected] => NULL
                        )
                    )
                )
                [_validators:protected] => Array()
                [_hasMin:protected] => string(1) 0
                [_hasMax:protected] => string(1) 5
                [_label:protected] => NULL
            )
        )
    )

note the ID field is added automaticly and validator objects are created. The
 schema pool is shared and stored in the SchemaManager object to avoid static
 scope issues and unnecessary instances. Also, original schema definition is
 removed from the model class after inflation so it does not come up at printouts.

get a collection by filters with Mongo sytanx:

    $VideoCollection = new \VideoCollection();
    $VideoCollection
        ->filter(
            array('length' => array('$gt' => 50))
        )
        ->loadByFilters();
    echop($VideoCollection);

this will give you something like:

    VideoCollection extends Maui\Collection (
        [_modelClassname:protected] => NULL
        [_data:protected] => Array(1) (
            [53eeb9b526e34be00595d011] => Array(6) (
                [_id] => MongoId Object (
                    [$id:public] => string(24) 53eeb9b526e34be00595d011
                )
                [user] => string(10) ArcheAdmin
                [title] => string(18) First movie object
                [subtitle] => string(19) First subtitle ever
                [length] => string(2) 54
                [staff] => Array(2) (
                    [0] => Array(1) (
                        [_id] => MongoId Object (
                            [$id:public] => string(24) 53f27f9126e34bb1049f5d4c
                        )
                    )
                    [1] => Array(1) (
                        [name] => string(14) Steven McQueen
                    )
                )
            )
        )
        [_filters:protected] => Array(1) (
            [0] => Array(1) (
                [length] => Array(1) (
                    [$gt] => string(2) 50
                )
            )
        )
    )

(note: all printouts use echop pretty printer)

