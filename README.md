# F0F NNRelation

FOF NNRelation add support to many-to-many relations into FOF (Framework on Framework). In truth it's for FOF!
It consist in a set of one F0FTableBehavior, one F0FModelBehavior and one F0FFormField. Add all these magic classes to get ready to use multiple relations (with pivot tables) in your F0F projects, with ease!

## Requirements

1. Joomla 3.x (it should works also with 2.5.x)
2. FOF 2.3.x or greater (download [Framework on Framework](https://www.akeebabackup.com/download/fof.html)

## Preparation

1. Install `lib_f0f-nnrelation-1.0.0.tgz` library extension
2. Create a `dispatcher.php` file for your FOF component
3. Override the method `onBeforeDispatch`
4. Before calling `parent::onBeforeDispatch` include f0f-nnrelation in this way:

```php
class FoobarDispatcher extends F0FDispatcher
{
  public function onBeforeDispatch()
  {
    // Add multiple to multiple relations support
    jimport('f0f-nnrelation.fields.nnrelation');
    jimport('f0f-nnrelation.models.behaviors.nnrelation');
    jimport('f0f-nnrelation.tables.behaviors.nnrelation');
    
    return parent::onBeforeDispatch();
  }
}
```

## Usage
#### Table Behavior

In your `form.form.xml` files you can use a new type of field called `nnrelation`.

```xml
<field name="players"
       type="nnrelation"
       multiple="true"
       label="COM_FOOBAR_FIELD_PLAYERS_LABEL"
       tooltip="COM_FOOBAR_FIELD_PLAYERS_DESCRIPTION"/>
```

The name attribute **must** be the same of the name of the multiple relation you declared in the `fof.xml` file.
In the exemple above, the multiple relation I want to manage is declared in this way:

```xml
<table name="team">
  <relation name="players"
            type="multiple"
            pivotTable="#__foobar_players_teams"/>
  <behaviors>nnrelation</behaviors>
</table>
```

As you see, you **must** enable the specific FOF behavior for the table in which you want to use the multiple relation.
In this example, the multiple relation will be automagically managed for all `team` items that want to trace their `players`. If you want to do viceversa, you have to declare the contrary `teams` relation for the `player` table, and enabling the behavior there. The names respects the standard FOF singular/plural conventions.

### Model Behavior

In the front-end of your component you want to retrieve your multiple relations with ease. For this purpose you can enable the `nnrelation` behavior for a view, and if here there is a multiple relation declared, all related items will be automagically retrieved.

```xml
<view name="teams">
  <config>
    <option name="behaviors">nnrelation</option>
  </config>
</view>
```

Now in your views, the `$this->items` data object will contains a property named as the relation.

```
stdClass Object
(
  [title] => Foobar Team
  [players] => stdClass Object
    (
      [0] => Array
        (
          [title] => John
          [...] => ...
        )
      [2] => ...
    )
)
```

### Form Field

In your `form.default.xml` files you can use a new type of field called `nnrelation`.

```xml
<field name="players"
       type="nnrelation"
       empty_replacement="COM_FOOBAR_NO_PLAYERS_LABEL"
       url="index.php?option=com_foobar&amp;view=player&amp;id=[ITEM:FOOBAR_PLAYER_ID]"
       show_link="true"/>
```

Also in this case, the name of the field should reflect the name of the multiple relation declared in `fof.xml`. The other attribures are inherited from the `list` form type (see FOF documentation). The placeholder `[ITEM:FOOBAR_PLAYER_ID]` is the name of the key value for your referred table, in the pivot table.

### Form Header

Coming Soon...
