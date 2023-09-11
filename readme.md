## Tasks API in PHP's Symfony
A simple API using PHP's Symfony. It has some basic functions, like:

- User registration and authentication using JWT
- Perform CRUD operations of Tasks
- Some basics business rules enforced

My main goal is to showcase some concepts, such as:

- Migrations and model relationship
- Authentication configuration
- Routes definitions
- SOLID and KISS principles on a MVC architecture

The main tech behind it is PHP, mainly the Symfony framework. I'm also using some others libraries. For storaging, i'm using MySQL relational database

## Installation and Configuration
This app uses Docker, so you should have it up and running beforehand. Then clone this rep and cd into the project's folder. Before anything create a local copy of the .env file by running: 
```
cp .env.local .env
```
Open the newly created file, and in place of the {}'s information, use your database connection information. Having done that, run the following:

```
docker-compose build
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php php bin/console doctrine:migrations:migrate
docker-compose exec php php bin/console lexik:jwt:generate-keypair
```

## Usage
#### __User creation__
If it goes well it should return a 200 status response.
```
curl --location 'http://localhost:8015/login' \
--header 'Content-Type: application/json' \
--data-raw '{
    "username": "t.soprano@mail.com",
    "password": "123456"
}'
```

#### __User authenticate__
Copy the token retrieved by that endpoint under the "token" attribute. Add it to the 'Authorization: Bearer ' header on all Task related requests
```
curl --location 'http://localhost:8015/login' \
--header 'Content-Type: application/json' \
--data-raw '{
    "username": "t.soprano@mail.com",
    "password": "123456"
}'
```

#### __Create Task__
That endpoint create a new Task. All these attributes are required. It will the token's authenticated user as the Owner
```
curl --location 'http://localhost:8015/api/task' \
--header 'Authorization: Bearer {place_token_here}' \
--header 'Content-Type: application/json' \
--data '{
    "title": "Insert Task Title Here",
    "description": "This is the task Description, it can be a really really really long text",
    "type": "feature"
}'
```

#### __Retrieve Tasks__
That endpoint retrieve all tasks that you create
```
curl --location 'http://localhost:8015/api/tasks' \
--header 'Authorization: Bearer {place_token_here}'
```

#### __Update Task__
That endpoint updates information of an existing task.
- Replace {```taskId```} with the id of an existing Task
```
curl --location --request PUT 'http://localhost:8015/api/task/{taskId}' \
--header 'Authorization: Bearer {place_token_here}' \
--header 'Content-Type: application/json' \
--data '{
    "description": "this is the task new description"
}'
```

#### __Close Task__
That endpoint closes an existing task. 
- Replace {```taskId```} with the id of an existing Task
```
curl --location --request PUT 'http://localhost:8015/api/task/{taskId}/close' \
--header 'Authorization: Bearer {place_token_here}'
```

#### __Delete Task__
That endpoint deletes an existing task.
- Replace {```taskId```} with the id of an existing Task
```
curl --location --request DELETE 'http://localhost:8015/api/task/{taskId}' \
--header 'Authorization: Bearer {place_token_here}'
```
