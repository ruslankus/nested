DROP TABLE IF EXISTS "test_categories";
CREATE TABLE test_categories
(
    id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    left_key INTEGER NOT NULL,
    right_key INTEGER NOT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX left_key ON test_categories ( left_key, right_key );

INSERT INTO "test_categories" VALUES(2,'Child',2,3);
INSERT INTO "test_categories" VALUES(3,'LOL',5,6);
INSERT INTO "test_categories" VALUES(4,'How Much Is The Fish?',1,4);