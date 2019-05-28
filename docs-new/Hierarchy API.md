# Hierarchy API

# JSON Endpoints


<!-- START_f6d838bb700192d56d216ae84700c66d -->
## Create/update a content hierarchy.


### HTTP Request
    `PUT railcontent/content/hierarchy`


###Permissions


### Request Parameters


|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
    |body|  data.type |  required  | | string  | Must be 'contentHierarchy'. |
    |body|  data.attributes.child_position |  optional  | | integer  | The position relative to the other children of the given parent. Will automatically shift other children. If null - position will be set to the end of the child stack. |
    |body|  data.relationships.parent.data.type |  optional  | | string  | Must be 'content'. |
    |body|  data.relationships.parent.data.id |  optional  | | integer  | Must exists in contents. |
    |body|  data.relationships.child.data.type |  optional  | | string  | Must be 'content'. |
    |body|  data.relationships.child.data.id |  optional  | | integer  | Must exists in contents. |


### Example request:

```js
$.ajax({
    url: 'https://www.domain.com' +
             '/railcontent/content/hierarchy',
{
    "data": {
        "type": "contentHierarchy",
        "attributes": {
            "child_position": 17
        },
        "relationships": {
            "parent": {
                "data": {
                    "type": "content",
                    "id": 1
                }
            },
            "child": {
                "data": {
                    "type": "content",
                    "id": 1
                }
            }
        }
    }
}
   ,
    success: function(response) {},
    error: function(response) {}
});
```

### Example response (422):

```json
{
    "errors": [
        {
            "title": "Validation failed.",
            "source": "data.relationships.child.data.id",
            "detail": "The child field is required."
        },
        {
            "title": "Validation failed.",
            "source": "data.relationships.parent.data.id",
            "detail": "The parent field is required."
        }
    ]
}
```




<!-- END_f6d838bb700192d56d216ae84700c66d -->

<!-- START_522506d0e5c355eb192c83407b0da522 -->
## railcontent/content/hierarchy/{parentId}/{childId}

### HTTP Request
    `DELETE railcontent/content/hierarchy/{parentId}/{childId}`


###Permissions


### Request Parameters


|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|


### Example request:

```js
$.ajax({
    url: 'https://www.domain.com' +
             '/railcontent/content/hierarchy/1/1',
[]
   ,
    success: function(response) {},
    error: function(response) {}
});
```

### Example response (204):

```json
null
```




<!-- END_522506d0e5c355eb192c83407b0da522 -->
