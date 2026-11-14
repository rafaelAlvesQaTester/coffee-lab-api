\getenv test_password COFFEE_LAB_TEST_DB_PASSWORD
CREATE ROLE :"test_user" LOGIN PASSWORD :'test_password';
GRANT CONNECT ON DATABASE :"database_name" TO :"test_user";
GRANT USAGE ON SCHEMA public TO :"test_user";
GRANT SELECT, INSERT, UPDATE ON TABLE public.coffees TO :"test_user";
