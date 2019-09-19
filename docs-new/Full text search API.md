# Full text search API

# JSON Endpoints


<!-- START_8009b1b4a1fe14e999c1ed8b25cbcd76 -->
## Full text search in contents


### HTTP Request
    `GET railcontent/search`


### Permissions

### Request Parameters


|Type|Key|Required|Notes|
|----|---|--------|-----|
|body|term|  yes  |Serch criteria.|
|body|included_types|    |Contents with these types will be returned.|
|body|statuses|    |All content must have one of these statuses. By default:published.|
|body|sort|    |Defaults to descending order; to switch to ascending order remove the minus sign (-). Can be any of the following: score or content_published_on. By default:-score.|
|body|brand|    |Contents from the brand will be returned.|
|body|page|    |Which page to load, will be {limit} long.By default:1.|
|body|limit|    |How many to load per page. By default:10.|


### Request Example:

```js
$.ajax({
    url: 'https://www.domain.com' +
             '/railcontent/search',
{
    "term": "Expanding The Triple Paradiddle",
    "included_types": "",
    "statuses": "published",
    "sort": "-score",
    "brand": "brand",
    "page": 1,
    "limit": 10
}
   ,
    success: function(response) {},
    error: function(response) {}
});
```

### Response Example (200):

```json
{
    "data": [
        {
            "type": "content",
            "id": "1",
            "attributes": {
                "slug": "Quod a velit tenetur cupiditate voluptatum quis aliquam eaque. Ut exercitationem nesciunt quia voluptas.",
                "type": "courses",
                "sort": "420510213",
                "status": "published",
                "brand": "brand",
                "language": "Qui deserunt similique quasi eos. Ut odio doloremque et non nulla iure quo. Sunt aspernatur labore velit quis non eum numquam. Repudiandae omnis sed mollitia quia exercitationem quia doloremque.",
                "user": "",
                "publishedOn": "2019-06-05 09:34:35",
                "archivedOn": {
                    "date": "1984-12-21 12:14:48.000000",
                    "timezone_type": 3,
                    "timezone": "UTC"
                },
                "createdOn": {
                    "date": "2006-09-30 03:07:28.000000",
                    "timezone_type": 3,
                    "timezone": "UTC"
                },
                "difficulty": "Voluptatum sunt nihil libero velit omnis natus ipsam et. Earum eos itaque odit. Repellendus ducimus facilis in nam. Dolorem omnis id culpa voluptatem quidem animi nihil.",
                "homeStaffPickRating": "1521161122",
                "legacyId": 939627393,
                "legacyWordpressPostId": 1075801389,
                "qnaVideo": "Aut voluptatum recusandae dolores non. Cumque nostrum occaecati quod porro. Rerum velit accusamus quae sit sit consequuntur dolor. Nesciunt quam vitae commodi quis dolorem quasi.",
                "style": "Nihil non cum nemo et est. Porro ullam pariatur sed veniam. Quo tempora magnam molestias iure vero voluptas enim.",
                "title": "Expanding The Triple Paradiddle",
                "xp": 1375568996,
                "album": "Et placeat quia odio omnis. Sit quae maiores illum natus. Quibusdam qui itaque laudantium corporis dolorem similique.",
                "artist": "Eveniet porro quaerat facere ratione distinctio. Dicta esse in exercitationem rerum quae itaque. Cum reiciendis consequuntur ad doloremque facilis sint molestiae. Tempore sit ipsa nisi error.",
                "bpm": "Sunt et facilis quod ex error. Quaerat libero dolore expedita dolore incidunt. Enim veniam dolores tempore natus id animi ut consequatur. Aperiam eius occaecati blanditiis. Voluptatem doloremque id illo sit illo.",
                "cdTracks": "Est doloribus nostrum autem possimus. Aut laboriosam quia blanditiis quo et expedita. Maxime accusamus omnis aliquid sit quia perferendis non consequatur. Dicta culpa corrupti aperiam ut eveniet magnam expedita. Dolores id rerum aperiam.",
                "chordOrScale": "Cupiditate pariatur minima est libero velit hic qui et. Ea ipsum ad omnis sequi ab id. Suscipit porro et accusamus commodi perferendis perferendis.",
                "difficultyRange": "Aspernatur natus voluptatem et et. Ullam omnis quis doloribus corporis. Rem veniam nulla consectetur eligendi voluptas veritatis voluptas. Molestiae fuga debitis quisquam excepturi dolores et quis et.",
                "episodeNumber": 2098035918,
                "exerciseBookPages": "Quibusdam ad officiis ex ut aliquam. Eum ratione nisi qui est excepturi iure placeat. Ut sunt adipisci quia at omnis. Esse veritatis velit dolor fugit quis et sint.",
                "fastBpm": "Soluta voluptatem aperiam saepe necessitatibus. Reprehenderit debitis enim non tempore omnis. Reprehenderit aut et ut inventore aut culpa veniam.",
                "includesSong": false,
                "instructors": "Facilis magnam sed sit expedita hic error. Doloremque ratione mollitia est ducimus quasi odit. Neque reprehenderit voluptates nulla quod accusamus.",
                "liveEventStartTime": {
                    "date": "1971-12-23 19:34:19.000000",
                    "timezone_type": 3,
                    "timezone": "UTC"
                },
                "liveEventEndTime": {
                    "date": "1973-06-21 09:19:31.000000",
                    "timezone_type": 3,
                    "timezone": "UTC"
                },
                "liveEventYoutubeId": "Alias aut magni sed harum. Ab at omnis id illum. Et recusandae ducimus non illo aut quibusdam cupiditate. Quisquam voluptas expedita rerum ab voluptatem aperiam. Facilis nemo doloribus distinctio consectetur minima. Mollitia perferendis sit dolor autem.",
                "liveStreamFeedType": "Illo in eos hic nihil. Soluta qui suscipit consectetur impedit sed quis pariatur corrupti. Qui iusto ex et dolorem optio deleniti. Nisi quia eligendi recusandae enim.",
                "name": "Laudantium iste quod voluptatem autem ad adipisci sunt quia. Dolorem molestiae ut odit voluptas dignissimos aut delectus. Itaque molestiae magnam quia ullam. Corrupti illo blanditiis suscipit velit illum architecto.",
                "released": "Quam accusamus necessitatibus rerum porro. Nemo omnis aut sed et harum ipsa voluptas. Sunt ipsam delectus id id. Vero ducimus earum odio explicabo necessitatibus voluptas vel dolorem.",
                "slowBpm": "Cum esse autem qui et velit. Vel tempore et praesentium ullam ut aut. Vitae et consectetur vel rerum ipsam quos voluptatem.",
                "totalXp": "Sed nulla labore eum ut ullam consequatur accusantium. Soluta quia ea sint totam consequatur. Deserunt dolore corporis nisi deleniti similique quasi.",
                "transcriberName": "Doloribus dolorem rerum qui ratione aut. Maxime velit et atque qui iusto enim. Rerum unde ea distinctio illum blanditiis quaerat. Repudiandae sit commodi natus quaerat.",
                "week": 374778595,
                "avatarUrl": "Et quos quos assumenda consectetur quo. Blanditiis ratione qui vel. Minus aut at similique eaque eius est. Facere reiciendis quam officiis.",
                "lengthInSeconds": 950938325,
                "soundsliceSlug": "Et et quis consequatur beatae. Similique incidunt nihil aut quia. Ut suscipit dicta hic in. Provident quis eius dolores sint non quidem maiores. Inventore tempora nisi culpa illum molestias. Officiis vel ut et rem perferendis aut voluptatem eius.",
                "staffPickRating": 1115616584,
                "studentId": 1638495279,
                "vimeoVideoId": "Cumque doloribus harum id aliquam hic maiores. Ut eos ipsa amet distinctio praesentium illum voluptas. Error aut alias vero dolor tenetur. Qui iste quod autem debitis.",
                "youtubeVideoId": "Et quis labore sed aut. Provident sunt rerum qui perspiciatis ea tempora. Quia rerum dolores accusantium."
            },
            "relationships": {
                "topic": {
                    "data": [
                        {
                            "type": "topic",
                            "id": "1"
                        }
                    ]
                }
            }
        },
        {
            "type": "content",
            "id": "2",
            "attributes": {
                "slug": "Expedita voluptates voluptas sit fuga. Qui amet deleniti qui consectetur eum.",
                "type": "courses",
                "sort": "1496634991",
                "status": "published",
                "brand": "brand",
                "language": "Voluptatem doloribus ipsa quia accusantium illum voluptatum omnis cumque. Eaque qui rerum ullam quia debitis ea. Voluptatum sed enim id dolorem vel.",
                "user": "",
                "publishedOn": "2019-06-05 09:34:35",
                "archivedOn": {
                    "date": "2001-04-09 03:56:34.000000",
                    "timezone_type": 3,
                    "timezone": "UTC"
                },
                "createdOn": {
                    "date": "1991-11-09 14:53:37.000000",
                    "timezone_type": 3,
                    "timezone": "UTC"
                },
                "difficulty": "Alias ut iure sit atque praesentium. Vitae libero qui aut et. Labore eveniet esse autem dolorem explicabo.",
                "homeStaffPickRating": "2093710967",
                "legacyId": 1579927331,
                "legacyWordpressPostId": 1996981447,
                "qnaVideo": "Qui id vel sed excepturi corrupti quae. Molestiae alias blanditiis laudantium tempora animi. Non libero quos aut reprehenderit optio tenetur.",
                "style": "Et amet ut qui excepturi nihil consequatur. Iste illum incidunt harum quo qui laboriosam. Vel unde voluptatem cumque eaque qui ex delectus. Voluptate sint et nisi eius et.",
                "title": "Paradiddle aut",
                "xp": 34220021,
                "album": "Sit ab corporis rerum autem. Labore sapiente non eum id. Voluptatem sed laboriosam placeat asperiores ratione. Labore eius ea et fugit quod aut officiis. Impedit at dolorem aut similique.",
                "artist": "Ea beatae sit pariatur iure aut maxime dignissimos. Nihil provident aut exercitationem. Error harum consequuntur voluptatem. Est iusto cupiditate ut nihil dolore.",
                "bpm": "Totam delectus consequuntur fuga sunt aut non. Voluptatem illo nobis eveniet voluptas. Vel et blanditiis commodi non. Sed tempora amet aut possimus optio nulla iste.",
                "cdTracks": "Mollitia a in quibusdam error molestiae et porro. Temporibus nihil repellendus illum. Qui ducimus unde ducimus id quo dolor mollitia. Doloribus ratione magni quidem ea.",
                "chordOrScale": "Ad voluptas necessitatibus ratione quo sed consequatur. Quia consequatur aut et aut corporis. Nesciunt libero ducimus sed velit. Molestiae earum aspernatur et minima non.",
                "difficultyRange": "Nostrum iure eum consequatur nihil deserunt. Voluptatum suscipit architecto aliquid quaerat. Est nesciunt ducimus cupiditate. Eius impedit vitae est qui corporis voluptatem ipsa explicabo.",
                "episodeNumber": 599181401,
                "exerciseBookPages": "Dolorem et beatae vitae culpa. Quam illo voluptas accusamus fuga asperiores blanditiis cumque. Labore id et aliquam et qui asperiores autem. Earum dolores perspiciatis quasi autem et.",
                "fastBpm": "Sequi et pariatur ducimus repellat nisi veniam aut. Perspiciatis sit velit in laborum. Voluptates aut aut quia autem dolore rem aut exercitationem. Sit eos quae maiores inventore ab. Assumenda iusto ut incidunt impedit.",
                "includesSong": false,
                "instructors": "Consequuntur hic consectetur expedita natus. Ut eos aut voluptatem voluptas beatae quasi voluptatem. Odit neque quaerat fugit beatae dolor voluptatum assumenda. Id aut quas totam est qui architecto.",
                "liveEventStartTime": {
                    "date": "1973-09-11 14:33:23.000000",
                    "timezone_type": 3,
                    "timezone": "UTC"
                },
                "liveEventEndTime": {
                    "date": "2003-05-26 22:52:43.000000",
                    "timezone_type": 3,
                    "timezone": "UTC"
                },
                "liveEventYoutubeId": "Dolores magni porro optio voluptas qui. Corrupti doloribus praesentium quia enim doloremque. Ut laborum nemo earum iste.",
                "liveStreamFeedType": "Nemo consequatur temporibus sint eveniet sed. Ducimus omnis placeat et ab nobis rerum ipsa. Sit dignissimos est inventore. Dolores a nobis occaecati praesentium est sint optio.",
                "name": "Natus id et in consequatur odit. Repellendus fugit voluptatem et. Vero voluptatem dolore praesentium voluptatibus incidunt repudiandae est veritatis. Ut quo ut molestias est. Voluptatibus et libero aut aut magnam magni.",
                "released": "At quia nam quis est. Nemo aliquid et perferendis magnam sed consequatur corrupti. Incidunt eum aut sint doloribus corporis voluptas non.",
                "slowBpm": "Et est quos quia eum dolor ipsa. Eius dolore commodi provident velit provident soluta nostrum. Quia minima id magnam voluptatem officia. A esse totam quaerat consequuntur voluptatem voluptatibus. Vel et corrupti qui repellat.",
                "totalXp": "Repudiandae quo iste provident pariatur ut ut. Repellat doloribus nihil et illo qui. Error quia nihil ipsum unde atque excepturi voluptatem.",
                "transcriberName": "Porro dolorem alias vel sit sit et voluptatem. Sit possimus et distinctio dolore. Consectetur amet voluptatem porro non molestiae. Ut nemo voluptatem ut optio. Nesciunt distinctio et et asperiores. Sunt dolorem quia ipsa aut consectetur ducimus sapiente.",
                "week": 1317445545,
                "avatarUrl": "Quia provident in est. Voluptatum soluta similique dolorem quia ut. Rerum sint qui aut quis ut dolor voluptatem maiores. Eum ea nostrum unde dolor. Doloremque ut sed rerum placeat omnis. Iusto dolor architecto dolorum praesentium aliquid voluptas sunt.",
                "lengthInSeconds": 1613706766,
                "soundsliceSlug": "Perferendis et harum aut occaecati et dolor. Modi error dolor magni. Est beatae omnis aut. Modi voluptatem inventore occaecati accusantium dolore.",
                "staffPickRating": 1692902600,
                "studentId": 931051369,
                "vimeoVideoId": "Quia necessitatibus tempora nam voluptas qui qui. Nihil aliquid delectus quia laudantium sit mollitia. Quae atque ratione dolores quia.",
                "youtubeVideoId": "Nesciunt repudiandae necessitatibus nemo aut tenetur. Illo et ipsam magnam dolorum quisquam accusamus. Omnis voluptatem est libero sunt."
            }
        }
    ],
    "included": [
        {
            "type": "topic",
            "id": "1",
            "attributes": {
                "topic": "excepturi",
                "position": 860008442
            }
        }
    ],
    "meta": {
        "pagination": {
            "total": 2,
            "count": 2,
            "per_page": 10,
            "current_page": 1,
            "total_pages": 1
        }
    },
    "links": {
        "self": "http:\/\/localhost\/railcontent\/search?page=1&limit=10&term=Expanding+The+Triple+Paradiddle",
        "first": "http:\/\/localhost\/railcontent\/search?page=1&limit=10&term=Expanding+The+Triple+Paradiddle",
        "last": "http:\/\/localhost\/railcontent\/search?page=1&limit=10&term=Expanding+The+Triple+Paradiddle"
    }
}
```




<!-- END_8009b1b4a1fe14e999c1ed8b25cbcd76 -->
