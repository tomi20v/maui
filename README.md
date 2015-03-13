Maui is a minimal MongoDB mapper for PHP
Note this is a hobby project and is not and is not meant to be complete.
Originally just a weekend project, now I have some days of work in it.

The basic idea is to provide a framework:
 - define objects by schema, including their attributes and relations
 - easily create and access data and relatives
 - basic CRUD (hard-coded MongoDB) for nested objects, including deep saves of
    inline and referred data
 - validation, concept: define fields by validators
 - be extensible for projects which use it

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

examples: create a Page object with known property, and load by that

	$Page = new \ModPageModel(['slug'=>'index']);
	$Page->load();

	echop($Page->title);

	echop($Page);

will produce something like:

    string(26) Demo Application home page

	ninja\ModPageModel extends ninja\ModAbstractModel (
		const [JS_HEAD] => HEAD
		const [JS_FOOT] => FOOT
		static [_schema:protected] => bool(true)
		[_Asset:protected] => NULL
		[_Module:protected] => NULL
		[_originalData:protected] => Array(8) (
			[slug] => string(5) index
			[_id] => MongoId Object (
				[$id:public] => string(24) 55023718ace34b2f0a6819cd
			)
			[_type] => string(18) ninja\ModPageModel
			[Parent] => string(24) 55023718ace34b2f0a6819cb
			[published] => bool(true)
			[Modules] => Array(1) (
				[columns] => Array(3) (
					[_type] => string(23) ninja\ModContainerModel
					[Contents] => Array(3) (
						[left] => string(38)
	Left dummy

						[middle] => string(40)
	Middle dummy

						[right] => string(39)
	Right dummy

					)
					[cssClasses] => Array(1) (
						[0] => string(3) row
					)
				)
			)
			[Root] => string(24) 55023718ace34b2f0a6819cb
			[title] => string(26) Demo Application home page
		)
		[_data:protected] => Array()
	)

now, add an (otherwise ambigous) module:

    $Page->Modules->add(['title'=>'New module', 'html'=>'<strong>asd</strong>'], 'newModule');

    echop($Page);

you get something like this:

	ninja\ModPageModel extends ninja\ModAbstractModel (
		const [JS_HEAD] => HEAD
		const [JS_FOOT] => FOOT
		static [_schema:protected] => bool(true)
		[_Asset:protected] => NULL
		[_Module:protected] => NULL
		[_originalData:protected] => Array(8) (
			[slug] => string(5) index
			[_id] => MongoId Object (
				[$id:public] => string(24) 55023718ace34b2f0a6819cd
			)
			[_type] => string(18) ninja\ModPageModel
			[Parent] => string(24) 55023718ace34b2f0a6819cb
			[published] => bool(true)
			[Modules] => Array(1) (
				[columns] => Array(3) (
					[_type] => string(23) ninja\ModContainerModel
					[Contents] => Array(3) (
						[left] => string(38)
	Left dummy

						[middle] => string(40)
	Middle dummy

						[right] => string(39)
	Right dummy

					)
					[cssClasses] => Array(1) (
						[0] => string(3) row
					)
				)
			)
			[Root] => string(24) 55023718ace34b2f0a6819cb
			[title] => string(26) Demo Application home page
		)
		[_data:protected] => Array(1) (
			[Modules] => maui\Collection Object (
				[_modelClassname:protected] => string(22) ninja\ModAbstractModel
				[_data:protected] => Array(2) (
					[columns] => Array(3) (
						[_type] => string(23) ninja\ModContainerModel
						[Contents] => Array(3) (
							[left] => string(38)
	Left dummy

							[middle] => string(40)
	Middle dummy

							[right] => string(39)
	Right dummy

						)
						[cssClasses] => Array(1) (
							[0] => string(3) row
						)
					)
					[newModule] => Array(2) (
						[title] => string(10) New module
						[html] => string(20) asd
					)
				)
				[_matchedCount:protected] => NULL
				[_pagesCount:protected] => NULL
				[_filters:protected] => Array()
			)
		)
	)

note how the 'Modules' value of the $Page object was converted to a Collection
instance as stored in $Page->_data while _originalData retains loaded value

this is how the Page object is defined:

	class ModPageModel extends \ModAbstractModel {

		const JS_HEAD = 'HEAD';
		const JS_FOOT = 'FOOT';

		protected static $_schema = [
			'@@extends' => 'ModBaseCssModel',
			// override parent to set a specific type
			'Parent' => [
				'class' => 'ModPageModel',
				'reference' => \SchemaManager::REF_REFERENCE,
			],
			'Root' => [
				'class' => 'ModPageModel',
				'reference' => \SchemaManager::REF_REFERENCE,
			],
			'slug' => [
				'toString',
				'required',
				// @todo it would be nice to implement :)
				//'uniqueInSiblings',
				'regexp' => \SchemaValidatorRegexp::STRING_ALNUM38,
			],
			// @obsolete marked for deletion, needs code check
			'doctype' => [
				'default' => 'html'
			],
			'title',
			'meta' => [
				'toArray',
				'keys' => ['name', 'content'],
				'hasMin' => 0,
				'hasMax' => 0,
			],
			'scripts' => [
				'toArray',
				'keys' => ['place', 'src', 'code'],
				'keysValues' => ['place', [\ModPageModel::JS_HEAD, \ModPageModel::JS_FOOT]],
				'keysEither' => ['src', 'code'],
				'hasMax' => 0,
			],
			'script' => [
				'toString',
			],
			'links' => [
				'toArray',
				'keys' => ['rel', 'href', 'media', 'onlyIf'],
				'keysValues' => ['rel', ['stylesheet', 'import']],
				'hasMax' => 0,
			],
			// @obsolete marked for deletion, needs code check
			'baseHref',
		];

		/**
		 * @var \ModPageModelAsset
		 */
		protected $_Asset;

		public static function getDbCollectionName() {
			return 'PageModelCollection';
		}

		/**
		...

		 */
	}



and this is something you'd get by

	echop(\SchemaManager::getSchema($Page));

printing the schema of Video object, after
 inflating (happens all automaticly):
parent,root,slug,doctype,title,meta,scripts,script,links,basehref
maui\Schema (
	[_schema:protected] => Array(23) (
		[_id] => maui\SchemaFieldAttr extends maui\SchemaFieldAbstract (
			[_key:protected] => string(3) _id
			[_required:protected] => bool(false)
			[_validators:protected] => Array(1) (
				[0] => maui\SchemaValidatorToId extends maui\\SchemaValidatorTo (
					const [FORMAT] => /^[0-9a-f]{24}$/
					[_value:protected] => string(4) toId
					[_parent:protected] => *RECURSION*
					[_isMulti:protected] => bool(false)
				)
			)
			[_default:protected] => NULL
			[_label:protected] => string(2) ID
		)
		[_type] => maui\SchemaFieldAttr extends maui\SchemaFieldAbstract (
			[_key:protected] => string(5) _type
			[_context:protected] => NULL
			[_hasMin:protected] => NULL
			[_hasMax:protected] => NULL
			[_required:protected] => bool(false)
			[_validators:protected] => Array(1) (
				[0] => maui\SchemaValidatorToType extends maui\SchemaValidatorTo (
					[_value:protected] => string(6) toType
					[_parent:protected] => *RECURSION*
					[_isMulti:protected] => bool(false)
				)
			)
			[_default:protected] => NULL
			[_label:protected] => string(4) type
		)
		[Parent] => maui\SchemaFieldRelative extends maui\SchemaFieldAbstract(
			[_class:protected] => string(12) ModPageModel
			[_reference:protected] => string(9) reference
			[_referredField:protected] => string(3) _id
			[_schema:protected] => NULL
			[_key:protected] => string(6) Parent
			[_context:protected] => NULL
			[_hasMin:protected] => NULL
			[_hasMax:protected] => NULL
			[_required:protected] => bool(false)
			[_validators:protected] => Array()
			[_default:protected] => NULL
			[_label:protected] => NULL
		)
		[published] => maui\SchemaFieldAttr extends maui\SchemaFieldAbstract (
			[_key:protected] => string(9) published
			[_context:protected] => NULL
			[_hasMin:protected] => NULL
			[_hasMax:protected] => NULL
			[_required:protected] => bool(false)
			[_validators:protected] => Array(1) (
				[0] maui\SchemaValidatorToBool extends \SchemaValidatorTo (
					[_value:protected] => string(6) toBool
					[_parent:protected] => *RECURSION*
					[_isMulti:protected] => bool(false)
				)
			)
			[_default:protected] => NULL
			[_label:protected] => NULL
		)
		[slug] => maui\SchemaFieldAttr extends ninja\SchemaFieldAbstract (
			[_key:protected] => string(4) slug
			[_context:protected] => NULL
			[_hasMin:protected] => NULL
			[_hasMax:protected] => NULL
			[_required:protected] => bool(true)
			[_validators:protected] => Array(2) (
				[0] => maui\SchemaValidatorToString extends \SchemaValidatorTo (
					[_value:protected] => string(8) toString
					[_parent:protected] => *RECURSION*
					[_isMulti:protected] => bool(false)
				)
				[1] => maui\SchemaValidatorRegexp extends maui\SchemaValidator (
					const [STRING_ALNUM38] => /^[a-z0-9_\-]*$/
					const [STRING_ALNUM64] => /^[a-zA-Z0-9_\-]*$/
					[_value:protected] => string(16) /^[a-z0-9_\-]*$/
					[_parent:protected] => *RECURSION*
					[_isMulti:protected] => bool(false)
				)
			)
			[_default:protected] => NULL
			[_label:protected] => NULL
		)

		...

	)
)

note the _id and _type fields are added automatically and validator objects are
created. The schema pool is shared and stored in the SchemaManager object to
avoid static scope issues and unnecessary instances. Also, original schema
definition is removed from the model class after inflation.

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


