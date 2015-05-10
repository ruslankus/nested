-- Table: test_categories

-- DROP TABLE test_categories;

CREATE TABLE test_categories
(
  id serial NOT NULL,
  name character varying(255) NOT NULL,
  left_key integer NOT NULL,
  right_key integer NOT NULL,
  CONSTRAINT test_categories_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE test_categories
  OWNER TO postgres;