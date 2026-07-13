-- Execute only the first time the volumen is created
-- Usefull for extensions, base schemas and additional users

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS pg_stat_statements;

-- Example: separated schema by domain
-- CREATE SCHEMA IF NOT EXISTS reporting;

