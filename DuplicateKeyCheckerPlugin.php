<?php declare(strict_types = 1);

class DuplicateKeyCheckerPlugin
{
	function tableIndexesPrint($indexes) {
		echo "<table cellspacing='0'>\n";
		$indexRedundancy = $this->getIndexRedundancy($indexes);

		foreach ($indexes as $name => $index) {
			ksort($index["columns"]); // enforce correct columns order
			$print = [];
			foreach ($index["columns"] as $key => $val) {
				$print[] = "<i>" . h($val) . "</i>"
					. ($index["lengths"][$key] ? "(" . $index["lengths"][$key] . ")" : "")
					. ($index["descs"][$key] ? " DESC" : "")
				;
			}
			echo "<tr title='" . h($name) . "'><th";
			if (in_array($name, array_keys($indexRedundancy))) {
				echo " class='error'>$index[type] <sup>*</sup>";
			} else {
				echo ">$index[type]";
			}
			echo " <td>" . implode(", ", $print) . "\n";
		}
		echo "</table>\n";

		if ($indexRedundancy) {
			echo "<p><sup>*</sup>Duplicitní index. Buďto je sloupec už součástí jiného indexu a je `nejvíc vlevo`. Nebo takový sloupec už jednou zaindexován je (například jako primární index).</p>";
		}

		return 1;
	}


	private function getIndexRedundancy(array $indexes): array
	{
		$indexColumns = [];
		$storage = [];
		$leftColumns = [];

		foreach ($indexes as $indexName => $index) {
			$leftColumn = reset($index['columns']);
			$newStorageRecord = [
				'type' => $index['type'],
				'columnsCount' => count($index['columns']),
				'leftColumn' => $leftColumn,
			];

			$storage[$indexName] = $newStorageRecord;

			if (isset($leftColumns[$leftColumn])) {
				$actualLeftColumnData = $storage[$leftColumns[$leftColumn]];
				if ($actualLeftColumnData['columnsCount'] > $newStorageRecord['columnsCount']) {
					unset($indexColumns[$leftColumns[$leftColumn]]);
				} elseif (
					$actualLeftColumnData['columnsCount'] === $newStorageRecord['columnsCount'] &&
					$actualLeftColumnData['type'] === 'PRIMARY'
				) {
					unset($indexColumns[$leftColumns[$leftColumn]]);
				} else {
					$indexName = $leftColumns[$actualLeftColumnData['leftColumn']];
				}

				$indexColumns[$indexName] = $leftColumn;
			}
			$leftColumns[$leftColumn] = $indexName;
		}
		echo "</pre>";

		return $indexColumns;
	}
}

return new DuplicateKeyCheckerPlugin();
