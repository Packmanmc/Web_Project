-- ----------------------------------------------------------
-- Script MYSQL pour mcd 
-- ----------------------------------------------------------


-- ----------------------------
-- Table: especes
-- ----------------------------
CREATE TABLE especes (
  id INT NOT NULL AUTO_INCREMENT,
  nom VARCHAR(200) NOT NULL,
  Feuillage VARCHAR(200) NOT NULL,
  CONSTRAINT especes_PK PRIMARY KEY (id)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: stade_dev
-- ----------------------------
CREATE TABLE stade_dev (
  id INT NOT NULL AUTO_INCREMENT,
  libelle VARCHAR(200) NOT NULL,
  CONSTRAINT stade_dev_PK PRIMARY KEY (id)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: quartiers
-- ----------------------------
CREATE TABLE quartiers (
  id INT NOT NULL AUTO_INCREMENT,
  quartier VARCHAR(200) NOT NULL,
  secteur VARCHAR(200) NOT NULL,
  CONSTRAINT quartiers_PK PRIMARY KEY (id)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: etat
-- ----------------------------
CREATE TABLE etat (
  id INT NOT NULL AUTO_INCREMENT,
  libelle VARCHAR(200) NOT NULL,
  CONSTRAINT etat_PK PRIMARY KEY (id)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: situations
-- ----------------------------
CREATE TABLE situations (
  id INT NOT NULL AUTO_INCREMENT,
  libelle VARCHAR(200) NOT NULL,
  CONSTRAINT situations_PK PRIMARY KEY (id)
)ENGINE=InnoDB;


-- ----------------------------
-- Table: arbre
-- ----------------------------
CREATE TABLE arbre (
  id_arbre INT NOT NULL AUTO_INCREMENT,
  X DOUBLE NOT NULL,
  Y DOUBLE NOT NULL,
  haut_tot FLOAT NOT NULL,
  haut_tronc FLOAT NOT NULL,
  diam_tronc FLOAT NOT NULL,
  age_estime INT NOT NULL,
  nb_diagnostic INT,
  date_plantage DATE,
  date_abattage DATE,
  remarquable TINYINT(1) NOT NULL,
  id_stade_dev INT NOT NULL,
  id_especes INT NOT NULL,
  id_etat INT NOT NULL,
  id_quartiers INT NOT NULL,
  id_situations INT NOT NULL,
  CONSTRAINT arbre_PK PRIMARY KEY (id_arbre),
  CONSTRAINT arbre_id_stade_dev_FK FOREIGN KEY (id_stade_dev) REFERENCES stade_dev (id),
  CONSTRAINT arbre_id_especes_FK FOREIGN KEY (id_especes) REFERENCES especes (id),
  CONSTRAINT arbre_id_etat_FK FOREIGN KEY (id_etat) REFERENCES etat (id),
  CONSTRAINT arbre_id_quartiers_FK FOREIGN KEY (id_quartiers) REFERENCES quartiers (id),
  CONSTRAINT arbre_id_situations_FK FOREIGN KEY (id_situations) REFERENCES situations (id)
)ENGINE=InnoDB;

