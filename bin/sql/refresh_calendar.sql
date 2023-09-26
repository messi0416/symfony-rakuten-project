DROP TABLE IF EXISTS calendar;
CREATE TABLE calendar (
    `date` DATE NOT NULL PRIMARY KEY
  , `year` SMALLINT NOT NULL COMMENT '西暦年'
  , `month` tinyint UNSIGNED NOT NULL COMMENT '月'
  , `day` tinyint UNSIGNED NOT NULL COMMENT '日'
  , wday VARCHAR(10) NOT NULL COMMENT '曜日: Sunday/Monday/Tuesday/Wednesday/Thursday/Friday/Saturday ※数値は基準がばらばらなので文字列: MySQL=>%W, PHP=>l'
) ENGINE=InnoDB DEFAULT CHARSET utf8 COMMENT = '日付マスタテーブル 1970/01/1 ～ 2030/12/31'
;

TRUNCATE calendar;
INSERT INTO calendar (
    `date`
  , `year`
  , `month`
  , `day`
  , `wday`
)
SELECT
    DATE_FORMAT( `date`, '%Y-%m-%d' ) AS `date`
  , CAST(DATE_FORMAT( `date`, '%Y' ) AS SIGNED) AS `year`
  , CAST(DATE_FORMAT( `date`, '%m' ) AS UNSIGNED) `month`
  , CAST(DATE_FORMAT( `date`, '%d' ) AS UNSIGNED) AS `day`
  , DATE_FORMAT( `date`, '%W' ) AS `wday`
FROM (
SELECT
  DATE_ADD('1970-01-01', INTERVAL td.seq DAY) AS `date`
FROM
  (
    SELECT
        0 seq
    FROM
      DUAL
    WHERE
      (@num := 0 - 1) * 0
    UNION ALL
    SELECT
        @num := @num + 1
    FROM tb_log
    LIMIT 22280
  ) AS td
) AS T
;
