delete from players where player_id not in(select player_id from accounts left join account_players on _account_id = account_id left join players on _player_id = player_id where active_email like 'tchal%' or active_email like '%lvak%' or (uname = 'Tchalvak' or uname ='Beagle' or uname = 'RobertoSuave'));
delete from accounts where account_id not in(select account_id from accounts left join account_players on _account_id = account_id left join players on _player_id = player_id where active_email like 'tchal%' or active_email like '%lvak%' or (uname = 'Tchalvak' or uname = 'Beagle' or uname = 'RobertoSuave'));
delete from players where player_id not in(select player_id from players where uname = 'Tchalvak' or uname = 'Beagle' or uname = 'RobertoSuave');
update accounts set phash = crypt('test', gen_salt('bf', 10));
update players set days = days/500, active = 1;  -- activate anyone who is left
update players set goals = '', beliefs = '';
update players set email = '' where email != 'tchalvakspam@gmail.com';
update accounts set active_email = 'test'||account_id||'@example.com', account_identity = 'test'||account_id||'@example.com' where active_email not like 'tchal%';
update accounts set last_ip = concat('33.', trunc(random()*55 + 1), '.', trunc(random()*250 + 1), '.33');
update clan set clan_name = 'clan_fixture_test'||clan_id;
update clan set description = 'fixtures_test';
update clan set clan_founder = 'Tchalvak';
update past_stats set stat_result = 'Tchalvak' where stat_result::text != '0';
truncate login_attempts;
truncate password_reset_requests;
truncate chat;
truncate player_rank;
truncate dueling_log;
truncate levelling_log;
truncate messages;
truncate enemies;
truncate events;
truncate inventory;
truncate settings;
-- May have to run tick atomic script after this