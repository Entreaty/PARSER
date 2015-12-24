set :application, "fairstays"
set :domain,      "root@linux-test7"
set :deploy_to,   "/var/www/fairstays-capistrano"
set :app_path,    "app"

# путь к git репозиторию
set :repository,  "git://github.com/Entreaty/PARSER.git"
# тип хранилища
set :scm,         :git

set  :user,       "GorbatkoAV"

set :model_manager, "doctrine"
# Or: `propel`

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain, :primary => true       # This may be the same as your `Web` server

set  :keep_releases,  3

# Be more verbose by uncommenting the following line
# logger.level = Logger::MAX_LEVEL
