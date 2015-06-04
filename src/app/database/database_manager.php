<?php

include_once(dirname(__FILE__) . "/../../../define.php");

class DatabaseManager
{
	private $link = null;

	// 接続
	public function connect() {

		$this->link = mysqli_connect( DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME );
		if (!$this->link) {
    		printf(mysqli_error($this->link));
		}
		else {
			// echo '<p>success';
			mysqli_set_charset( $this->link, 'utf8');
		}

	}

	// 検索
	public function select( $query ) {

		$res = array();
		if ($result = mysqli_query($this->link, $query)) {

    		/* 連想配列を取得します */
   			while ($row = mysqli_fetch_assoc($result)) {
				// echo '<p>';
   				// print($row['id']);
   				// print($row['blog_name']);
   				// print($row['photo_url']);
   				$res[count($res)] = $row; 
    		}

		    /* 結果セットを開放します */
		    mysqli_free_result($result);
			return $res;
		}
		return null;
	}

	// 挿入
	public function insert( $query ) {

		$result_flag = mysqli_query( $this->link, $query );

		// if (!$result_flag) {
		// 	echo '<p>';
		// 	print(mysqli_error($this->link));
		// 	echo '<p>insert failed';
		// }
		// else {
		// 	echo '<p>insert success';
		// }

		return $result_flag;
	}

	public function close() {

		$close_flag = mysqli_close($this->link);
		// if (!$close_flag) {
		// 	echo '<p>close failed';
		// }
		// else {
		// 	echo '<p>close success';
		// }

		return $close_flag;
	}
}
