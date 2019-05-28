# Content Likes API

# JSON Endpoints


<!-- START_6bf34590090ea43f90bc0b8aca783f73 -->
## Fetch likes for content with pagination.


### HTTP Request
    `GET railcontent/content-like/{id}`


###Permissions


### Request Parameters


|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|


### Example request:

```js
$.ajax({
    url: 'https://www.domain.com' +
             '/railcontent/content-like/1',
[]
   ,
    success: function(response) {},
    error: function(response) {}
});
```

### Example response (200):

```json
{
    "data": [],
    "meta": {
        "pagination": {
            "total": 0,
            "count": 0,
            "per_page": 10,
            "current_page": 1,
            "total_pages": 0
        }
    },
    "links": {
        "self": "http:\/\/localhost\/railcontent\/content-like\/1?page=1",
        "first": "http:\/\/localhost\/railcontent\/content-like\/1?page=1",
        "last": "http:\/\/localhost\/railcontent\/content-like\/1?page=0"
    }
}
```




<!-- END_6bf34590090ea43f90bc0b8aca783f73 -->

<!-- START_c864f9442ee531ba11d7259fb511a17c -->
## Authenticated user like content.


### HTTP Request
    `PUT railcontent/content-like`


###Permissions


### Request Parameters


|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
    |body|  data.relationships.content.data.type |  required  | | string  | Must be 'content'. |
    |body|  data.relationships.content.data.id |  required  | | integer  | Must exists in contents. |

### Validation Rules
```php
{
    "data.relationships.content.data.id": "required|numeric|exists:testbench.railcontent_content,id"
}
```

### Example request:

```js
$.ajax({
    url: 'https://www.domain.com' +
             '/railcontent/content-like',
{
    "data": {
        "relationships": {
            "content": {
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
            "source": "data.relationships.content.data.id",
            "detail": "The content field is required."
        }
    ]
}
```




<!-- END_c864f9442ee531ba11d7259fb511a17c -->

<!-- START_4f7915ff2544f600944155f3e2c529eb -->
## Authenticated user dislike content.


### HTTP Request
    `DELETE railcontent/content-like`


###Permissions


### Request Parameters


|Type|Key|Required|Default|Options|Notes|
|----|---|--------|-------|-------|-----|
    |body|  data.relationships.content.data.type |  required  | | string  | Must be 'content'. |
    |body|  data.relationships.content.data.id |  required  | | integer  | Must exists in contents. |

### Validation Rules
```php
{
    "data.relationships.content.data.id": "required|numeric|exists:testbench.railcontent_content,id"
}
```

### Example request:

```js
$.ajax({
    url: 'https://www.domain.com' +
             '/railcontent/content-like',
{
    "data": {
        "relationships": {
            "content": {
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
            "source": "data.relationships.content.data.id",
            "detail": "The content field is required."
        }
    ]
}
```




<!-- END_4f7915ff2544f600944155f3e2c529eb -->
