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
```
```
docker-compose up -d
```
```
docker-compose exec php composer install
```
```
docker-compose exec php php bin/console doctrine:migrations:migrate
```
```
docker-compose exec php php bin/console lexik:jwt:generate-keypair
```
If all containers are up and running without errors, then the app is ready for usage.

## Tests
Before running the integration tests to validate the application's features, it must be executed the command to run the migration. Symfony by default creates a separate database for tests, so the following must be executed:
```
docker-compose exec php php bin/console doctrine:migrations:migrate --env=test
```

Once the migratiom finishes, just run
```
docker-compose exec php php vendor/bin/phpunit tests/
```

## Usage
### __Users__
#### User creation
```
curl --location 'http://localhost:8015/register' \
--header 'Content-Type: application/json' \
--data-raw '{
    "email": "t.soprano@mail.com",
    "password": "123456"
}'
```

#### User authentication
```
curl --location 'http://localhost:8015/login' \
--header 'Content-Type: application/json' \
--data-raw '{
    "username": "t.soprano@mail.com",
    "password": "123456"
}'
```
### __Tasks__
Copy the token retrieved by the User authentication endpoint under the "token" attribute. Add it to the 'Authorization: Bearer ' header on all Task related requests
#### Creating a new Task
```
curl --location 'http://localhost:8015/api/task/create' \
--header 'Authorization: Bearer {place_token_here}' \
--header 'Content-Type: application/json' \
--data '{
    "title": "Insert Task Title Here",
    "description": "This is the task Description, it can be a really really really long text",
    "type": "feature"
}'
```

#### Listing all Tasks
Replace ```{created_by}``` with a User Id  
Replace ```{status}``` with any of the following: new, in_dev, in_qa, blocked, closed  
Replace ```{type}``` with any of the following: feature, bugfix, hotfix  
If none of these filters are present this endpoint will retrieve all existing Tasks
```
curl --location 'http://localhost:8015/api/task/list?type={type}&status={status}&created_by={created_by}' \
--header 'Authorization: Bearer {place_token_here}'
```

#### Selecting a single Task
```
curl --location 'http://localhost:8015/api/task/view/2' \
--header 'Authorization: Bearer {place_token_here}'
```

#### Updating a Task
```
curl --location --request PUT 'http://localhost:8015/api/task/update/{taskId}' \
--header 'Authorization: Bearer {place_token_here}' \
--header 'Content-Type: application/json' \
--data '{
    "description": "this is the task new description"
}'
```

#### Closing a Task
```
curl --location --request PUT 'http://localhost:8015/api/task/close/{taskId}' \
--header 'Authorization: Bearer {place_token_here}'
```

#### Deleting a Task
```
curl --location --request DELETE 'http://localhost:8015/api/task/delete/{taskId}' \
--header 'Authorization: Bearer {place_token_here}'
```
