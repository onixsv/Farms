<?php

namespace ifteam\Farms;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\world\World;

class Farms extends PluginBase implements Listener{

	/**
	 * @var Config
	 */
	public $farmConfig, $Config;

	/**
	 * @var array
	 */
	public $farmData, $configData;

	private $count, $countt, $counttt, $countttt;

	/**
	 * @var array
	 */
	public $crops = [
		[
			"item" => ItemIds::SEEDS,
			"block" => BlockLegacyIds::WHEAT_BLOCK
		],
		[
			"item" => ItemIds::CARROT,
			"block" => BlockLegacyIds::CARROT_BLOCK
		],
		[
			"item" => ItemIds::POTATO,
			"block" => BlockLegacyIds::POTATO_BLOCK
		],
		[
			"item" => ItemIds::BEETROOT,
			"block" => BlockLegacyIds::BEETROOT_BLOCK
		],
		[
			"item" => ItemIds::SUGARCANE,
			"block" => BlockLegacyIds::SUGARCANE_BLOCK
		],
		[
			"item" => ItemIds::SUGARCANE_BLOCK,
			"block" => BlockLegacyIds::SUGARCANE_BLOCK
		],
		[
			"item" => ItemIds::PUMPKIN_SEEDS,
			"block" => BlockLegacyIds::PUMPKIN_STEM
		],
		[
			"item" => ItemIds::MELON_SEEDS,
			"block" => BlockLegacyIds::MELON_STEM
		],
		[
			"item" => ItemIds::DYE,
			"block" => 127
		],
		[
			"item" => ItemIds::CACTUS,
			"block" => BlockLegacyIds::CACTUS
		]
	];

	protected function onEnable() : void{
		@mkdir($this->getDataFolder());

		$this->farmConfig = new Config($this->getDataFolder() . "farmlist.yml", Config::YAML);
		$this->farmData = $this->farmConfig->getAll();

		$this->Config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"task-execute-interval" => 20,
			"monitoring" => true,
			"monitoring-task" => true,
			"monitoring-farm-update" => false,
			"monitoring-farm-place-break" => false,
			"farm-update-limit" => 20,
			"farm-check-limit" => 10,
			"farm-growing-time" => 1000
		]);
		$this->configData = $this->Config->getAll();

		$this->getScheduler()->scheduleRepeatingTask(new FarmsTask($this), $this->configData["task-execute-interval"]);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		foreach(array_keys($this->farmData) as $key){
			if($this->farmData[$key]['t'] == $this->farmData[$key]['gt']){
				unset($this->farmData[$key]);
			}
			if(!isset($this->farmData[$key]['id'])){
				unset($this->farmData[$key]);
			}
			if(!isset($this->farmData[$key]['t'])){
				unset($this->farmData[$key]);
			}
		}
	}

	protected function onDisable() : void{
		$this->farmConfig->setAll($this->farmData);
		$this->farmConfig->save();
	}

	public function get($var){
		return $this->configData["$var"];
	}

	public function configSave(){
		$this->Config->setAll($this->configData);
		$this->Config->save();
	}

	public function debugMessage($message){
		$this->getServer()->getLogger()->info($message);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command){
			case "농작물":
				if(!isset($args[0])){
					$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 [전체갯수/강제성장/설정/정보/모니터링]");
					return true;
				}
				switch($args[0]){
					case "전체갯수":
						$output = count($this->farmData);
						$sender->sendMessage("§d<§f시스템§d> §f농작물 전체갯수 : §l§a" . $output);
						return true;

					case "강제성장":
						if(!isset($args[1])){
							$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 강제성장 [ (1~8)단계 ]");
							return true;
						}
						if(is_numeric($args[1])){
							if($args[1] > 8 || $args[1] < 1){
								$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 강제성장 [ (1~8)단계 ]");
								return true;
							}
							$output = count($this->farmData);
							for($i = 1; $i <= "$args[1]"; $i++){
								$this->forceupdate();
							}
							$sender->sendMessage("§d<§f시스템§d> §f농작물이 " . $args[1] . "단계 강제성장 처리되었습니다.");
							$sender->sendMessage("§d<§f시스템§d> §f강제성장된 농작물 갯수 : §l§a" . $output);
							return true;
						}
						break;

					case "정보":
						$this->get('monitoring') ? $m = "§a켜짐" : $m = "§7꺼짐";
						$this->get('monitoring-task') ? $mt = "§a" : $mt = "§7";
						$this->get('monitoring-farm-update') ? $mu = "§a" : $mu = "§7";
						$this->get('monitoring-farm-place-break') ? $mp = "§a" : $mp = "§7";
						$sender->sendMessage("§d<§f시스템§d> §f==== 농작물 정보 ====");
						$sender->sendMessage("§d<§f시스템§d> §f농작물 전체갯수 : " . count($this->farmData) . "개");
						$sender->sendMessage("§d<§f시스템§d> §f성장시간 : " . $this->get('farm-growing-time') . "초");
						$sender->sendMessage("§d<§f시스템§d> §f테스크주기 : " . ($this->get('task-execute-interval')) / 20 . "초 ( " . $this->get('task-execute-interval') . "틱 )");
						$sender->sendMessage("§d<§f시스템§d> §f처리제한 : " . $this->get('farm-update-limit') . "개");
						$sender->sendMessage("§d<§f시스템§d> §f체크제한 : " . $this->get('farm-check-limit') . "개");
						$sender->sendMessage("§d<§f시스템§d> §f현재 모니터링 상태 : " . $m);
						$sender->sendMessage("§d<§f시스템§d> §f모니터링에 표시되는 항목 : " . $mt . "테스크   " . $mu . "농작물업데이트   " . $mp . "농작물 설치/파괴");
						return true;

					case "모니터링":
						if(!isset($args[1])){
							$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 모니터링 [켜기/끄기]");
							$sender->sendMessage("§d<§f시스템§d> §f모니터링 설정 : /농작물 모니터링 [테스크표시/농작물업데이트표시/설치표시]");
							return true;
						}
						switch($args[1]){
							case "켜기":
								$this->configData["monitoring"] = true;
								$this->configSave();
								$sender->sendMessage("§d<§f시스템§d> §f모니터링을 켰습니다. (콘솔)");
								return true;

							case "끄기":
								$this->configData["monitoring"] = false;
								$this->configSave();
								$sender->sendMessage("§d<§f시스템§d> §f모니터링을 껐습니다. (콘솔)");
								return true;

							case "테스크표시":
								$this->configData["monitoring-task"] ? $this->configData["monitoring-task"] = false : $this->configData["monitoring-task"] = true;
								$this->configData["monitoring-task"] ? $i = "합" : $i = "하지 않습";
								$this->configSave();
								$sender->sendMessage("§d<§f시스템§d> §f모니터링에서 테스크를 표시" . $i . "니다.");
								return true;

							case "농작물업데이트표시":
								$this->get('monitoring-farm-update') ? $this->configData["monitoring-farm-update"] = false : $this->configData["monitoring-farm-update"] = true;
								$this->configData["monitoring-farm-update"] ? $i = "합" : $i = "하지 않습";
								$this->configSave();
								$sender->sendMessage("§d<§f시스템§d> §f모니터링에서 농작물 업데이트를 표시" . $i . "니다.");
								return true;

							case "설치표시":
								$this->get('monitoring-farm-place-break') ? $this->configData["monitoring-farm-place-break"] = false : $this->configData["monitoring-farm-place-break"] = true;
								$this->configData["monitoring-farm-place-break"] ? $i = "합" : $i = "하지 않습";
								$this->configSave();
								$sender->sendMessage("§d<§f시스템§d> §f모니터링에서 농작물 설치/파괴를 표시" . $i . "니다.");
								return true;

							default:
								$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 모니터링 [켜기/끄기]");
								$sender->sendMessage("§d<§f시스템§d> §f모니터링 설정 : /농작물 모니터링 [테스크표시/농작물업데이트표시/설치표시]");
								return true;
						}

					case "설정":
						if(!isset($args[1])){
							$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 설정 [성장시간/테스크주기/처리제한/체크제한]");
							return true;
						}
						switch($args[1]){
							case "테스크주기":
								if(!isset($args[2])){
									$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 설정 테스크주기 [단위 : 틱 (20틱 = 1초) ]");
									return true;
								}
								if(is_numeric($args[2])){
									$previoustick = $this->get('task-execute-interval');
									$this->configData['task-execute-interval'] = "$args[2]";
									$sender->sendMessage("§d<§f시스템§d> §f테스크주기가 변경되었습니다. 재부팅 후에 적용됩니다.");
									$sender->sendMessage("§d<§f시스템§d> §f이전 : " . $previoustick . "틱 / 현재 : " . $this->get('task-execute-interval') . "틱");
									$this->configSave();
									return true;
								}
								break;

							case "처리제한":
								if(!isset($args[2])){
									$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 설정 처리제한 [갯수]");
									return true;
								}
								if(is_numeric($args[2])){
									$previoustick = $this->get('farm-update-limit');
									$this->configData['farm-update-limit'] = "$args[2]";
									$sender->sendMessage("§d<§f시스템§d> §f처리제한이 변경되었습니다.");
									$sender->sendMessage("§d<§f시스템§d> §f이전 : " . $previoustick . "개 / 현재 : " . $this->get('farm-update-limit') . "개");
									$this->configSave();
									return true;
								}
								break;

							case "체크제한":
								if(!isset($args[2])){
									$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 설정 체크제한 [갯수]");
									return true;
								}
								if(is_numeric($args[2])){
									$previoustick = $this->get('farm-check-limit');
									$this->configData['farm-check-limit'] = "$args[2]";
									$sender->sendMessage("§d<§f시스템§d> §f체크제한이 변경되었습니다.");
									$sender->sendMessage("§d<§f시스템§d> §f이전 : " . $previoustick . "개 / 현재 : " . $this->get('farm-check-limit') . "개");
									$this->configSave();
									return true;
								}
								break;

							case "성장시간":
								if(!isset($args[2])){
									$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 설정 성장시간 [단위 : 초]");
									return true;
								}
								if(is_numeric($args[2])){
									$previoustick = $this->get('farm-growing-time');
									$this->configData['farm-growing-time'] = "$args[2]";
									$sender->sendMessage("§d<§f시스템§d> §f성장시간이 변경되었습니다.");
									$sender->sendMessage("§d<§f시스템§d> §f이전 : " . $previoustick . "초 / 현재 : " . $this->get('farm-growing-time') . "초");
									$this->configSave();
									return true;
								}
								break;

							default:
								$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 설정 [성장시간/테스크주기/처리제한/체크제한]");
								return true;
						}
						break;

					default:
						$sender->sendMessage("§d<§f시스템§d> §f사용법 : /농작물 [전체갯수/강제성장/설정/정보/모니터링]");
						return true;
				}
		}
		return true;
	}

	// /////////////////////////////////////////////////////////////////////
	// //////////////////////##블럭설치##///////////////////////////
	// /////////////////////////////////////////////////////////////////////
	public function onBlock(PlayerInteractEvent $event){
		$block = $event->getBlock()->getSide(1);

		if($event->getAction() == $event::RIGHT_CLICK_BLOCK){
			// Cocoa bean
			if($event->getItem()->getId() == ItemIds::DYE and $event->getItem()->getMeta() == 3){
				$tree = $event->getBlock()->getSide($event->getFace());
				// Jungle wood
				if($tree->getId() == BlockLegacyIds::WOOD and $tree->getMeta() == 3){
					if(!$event->getBlock()->getPosition()->getWorld()->isInLoadedTerrain($event->getBlock()->getPosition())){
						$event->getBlock()->getPosition()->getWorld()->loadChunk($event->getBlock()->getPosition()->getFloorX() >> 4, $event->getBlock()->getPosition()->getFloorZ() >> 4);
					}
					$event->getBlock()->getPosition()->getWorld()->setBlock($event->getBlock()->getSide($event->getFace())->getPosition(), BlockFactory::getInstance()->get($event->getFace(), 0), true);
					return;
				}
			}
			// Farmland or sand
			if($event->getBlock()->getId() == ItemIds::FARMLAND or $event->getBlock()->getId() == ItemIds::SAND){
				foreach($this->crops as $crop){
					if($event->getItem()->getId() == $crop["item"]){
						$key = $block->getPosition()->getX() . "." . $block->getPosition()->getY() . "." . $block->getPosition()->getZ();

						$this->farmData[$key]['id'] = $crop["block"];
						$this->farmData[$key]['d'] = 0;
						$this->farmData[$key]['l'] = $block->getPosition()->getWorld()->getFolderName();
						$this->farmData[$key]['t'] = $this->makeTimestamp(date("Y-m-d H:i:s"));
						$this->farmData[$key]['gt'] = ($this->get('farm-growing-time'));

						if($this->get('monitoring') && $this->get('monitoring-farm-place-break'))
							$this->debugMessage("§7모니터링 :: 농작물설치 ( " . $block->getPosition()->getX() . ", " . $block->getPosition()->getY() . ", " . $block->getPosition()->getZ() . " )");

						break;
					}
				}
			}
		}
	}

	// /////////////////////////////////////////////////////////////////////
	// /////////////////////##블럭파괴##////////////////////////////
	// /////////////////////////////////////////////////////////////////////
	public function onBlockBreak(BlockBreakEvent $event){
		$block = $event->getBlock();
		$key = $block->getPosition()->getX() . "." . $block->getPosition()->getY() . "." . $block->getPosition()->getZ();
		foreach($this->crops as $crop){
			if($event->getItem()->getId() == $crop["item"] and isset($this->farmData[$key])){
				unset($this->farmData[$key]);
				if($this->get('monitoring') && $this->get('monitoring-farm-place-break'))
					$this->debugMessage("§7모니터링 :: 농작물파괴");
			}
		}
	}

	// /////////////////////////////////////////////////////////////////////
	// ///////////////////////##테스크##/////////////////////////////
	// /////////////////////////////////////////////////////////////////////
	public function tick(){
		$foreachc = 0;
		$count = 0;
		$countt = 0;

		if($this->get('monitoring') && $this->get('monitoring-task'))
			$this->debugMessage("§7모니터링 :: :::::::::::::::::::: 테스크 ::::::::::::::::::::");

		// 반복문
		foreach(array_keys($this->farmData) as $key){
			if($count >= $this->get('farm-update-limit') && !$this->get('farm-update-limit') == 0){
				if($this->get('monitoring') && $this->get('monitoring-task')){
					$this->debugMessage("§7모니터링 :: 농작물 처리중단 ( 처리제한 " . $this->get('farm-update-limit') . "개 ) :: 전체 농작물 갯수 " . count($this->farmData) . "개");
				}
				break;
			}

			if($countt >= $this->get('farm-check-limit') && !$this->get('farm-check-limit') == 0){
				if($this->get('monitoring') && $this->get('monitoring-task')){
					$this->debugMessage("§7모니터링 :: 농작물 처리중단 :: ( 체크제한 " . $this->get('farm-check-limit') . "개 ) :: 전체 농작물 갯수 " . count($this->farmData) . "개");
					$this->debugMessage("§7모니터링 :: 검사한 농작물 갯수 " . $foreachc . "개");
				}
				break;
			}

			++$foreachc;

			if(!isset($this->farmData[$key]['id']) || !isset($this->farmData[$key]['t'])){
				unset($this->farmData[$key]);
				continue;
			}

			$progress = $this->makeTimestamp(date("Y-m-d H:i:s")) - $this->farmData[$key]['t'];
			if($progress < $this->farmData[$key]['gt']){
				++$countt;
				continue;
			}

			$level = isset($this->farmData[$key]['l']) ? $this->getServer()->getWorldManager()->getWorldByName($this->farmData[$key]['l']) : $this->getServer()->getWorldManager()->getDefaultWorld();

			if(!$level instanceof World){
				if($this->get('monitoring') && $this->get('monitoring-farm-update')){
					$this->debugMessage("§7모니터링 :: (" . $countt . ")Cancelled Reason : Level");
				}

				continue;
			}

			$coordinates = explode(".", $key);
			$position = new Vector3((int) $coordinates[0], (int) $coordinates[1], (int) $coordinates[2]);

			if($this->updateCrops($key, $level, $position)){
				unset($this->farmData[$key]);
				++$count;
				continue;
			}

			unset($this->farmData[$key]);
		} // foreach끝

		if($this->get('monitoring') && $this->get('monitoring-task')){
			$this->debugMessage("§7모니터링 :: 테스크 :: 처리된 농작물 : §b" . $count . "개§7 :: 전체 농작물 갯수 : " . count($this->farmData) . "개");
			$this->debugMessage("§7모니터링 :: 테스크 :: 검사한 농작물 갯수 " . $foreachc . "개");
			$this->debugMessage("§7모니터링 :: 테스크 :: 불발 농작물 갯수 " . $countt . "개");
		}
	}

	// /////////////////////////////////////////////////////////////////////
	// ///////////////##강제성장 테스크##/////////////////////////
	// /////////////////////////////////////////////////////////////////////
	public function forceupdate(){
		/* if(count($this->farmData) == 0) return true; */
		$updatedcrops = 0;
		$foreachc = 0;
		foreach(array_keys($this->farmData) as $key){
			++$foreachc;

			$level = isset($this->farmData[$key]['l']) ? $this->getServer()->getWorldManager()->getWorldByName($this->farmData[$key]['l']) : $this->getServer()->getWorldManager()->getDefaultWorld();
			if(!$level instanceof World)
				continue;

			$coordinates = explode(".", $key);
			$position = new Vector3((int) $coordinates[0], (int) $coordinates[1], (int) $coordinates[2]);

			if($this->updateCrops($key, $level, $position)){
				unset($this->farmData[$key]);
				++$updatedcrops;
				continue;
			}
			unset($this->farmData[$key]);

			if($this->get('monitoring') && $this->get('monitoring-task')){
				$this->debugMessage("§7모니터링 :: 농작물 강제성장 ( " . $foreachc . " 번째 ) :: " . (int) $coordinates[0] . ", " . (int) $coordinates[1] . ", " . (int) $coordinates[2] . " :: 전체 농작물 갯수 " . count($this->farmData) . "개");
			}
		}

		if($this->get('monitoring') && $this->get('monitoring-task')){
			$this->debugMessage("§7모니터링 :: 농작물 강제성장 처리갯수 : §b" . $updatedcrops . "개§7 :: 현재 농작물 갯수 : " . count($this->farmData) . "개");
		}
	}

	// /////////////////////////////////////////////////////////////////////
	// ////////////////////##타임스템프##//////////////////////////
	// /////////////////////////////////////////////////////////////////////
	public function makeTimestamp($date){
		$yy = substr($date, 0, 4);
		$mm = substr($date, 5, 2);
		$dd = substr($date, 8, 2);
		$hh = substr($date, 11, 2);
		$ii = substr($date, 14, 2);
		$ss = substr($date, 17, 2);
		return mktime($hh, $ii, $ss, $mm, $dd, $yy);
	}

	// /////////////////////////////////////////////////////////////////////
	// /////////////////##농작물 업데이트##///////////////////////
	// /////////////////////////////////////////////////////////////////////
	public function updateCrops($key, World $level, Vector3 $position){
		switch($this->farmData[$key]['id']){
			case BlockLegacyIds::WHEAT_BLOCK:
			case BlockLegacyIds::CARROT_BLOCK:
			case BlockLegacyIds::POTATO_BLOCK:
			case BlockLegacyIds::BEETROOT_BLOCK:
				return $this->updateNormalCrops($key, $level, $position);

			case BlockLegacyIds::SUGARCANE_BLOCK:
			case BlockLegacyIds::CACTUS:
				return $this->updateVerticalGrowingCrops($key, $level, $position);

			case BlockLegacyIds::PUMPKIN_STEM:
			case BlockLegacyIds::MELON_STEM:
				return $this->updateHorizontalGrowingCrops($key, $level, $position);

			default:
				return true;
		}
	}

	// /////////////////////////////////////////////////////////////////////
	// ///////////////////////##밀계열##/////////////////////////////
	// /////////////////////////////////////////////////////////////////////
	public function updateNormalCrops($key, World $level, Vector3 $position){
		if($this->get('monitoring') && $this->get('monitoring-farm-update'))
			$this->debugMessage("§7모니터링 :: 밀 계열 농작물 성장완료 :: 현재 농작물 갯수 " . count($this->farmData) . "개");

		if((int) $this->farmData[$key]["id"] > 0){
			if(!$level->isInLoadedTerrain($position)){
				$level->loadChunk($position->getFloorX() >> 4, $position->getFloorZ() >> 4);
			}
			if($level->getBlock($position)->getId() === $this->farmData[$key]["id"]){
				$level->setBlock($position, BlockFactory::getInstance()->get((int) $this->farmData[$key]["id"], 7));
			}
		}
		return true;
	}

	// /////////////////////////////////////////////////////////////////////
	// ///////////////////##선인장계열##///////////////////////////
	// /////////////////////////////////////////////////////////////////////
	public function updateVerticalGrowingCrops($key, World $level, Vector3 $position){
		if($this->get('monitoring') && $this->get('monitoring-farm-update'))
			$this->debugMessage("§7모니터링 :: 선인장 계열 농작물 성장완료 :: 현재 농작물 갯수 " . count($this->farmData) . "개");

		for($i = 0; $i <= 2; ++$i){
			//$cropPosition = $position->setComponents((int) $position->x, (int) $position->y + 1, (int) $position->z);
			$position->y += 1;
			$cropPosition = new Position($position->getFloorX(), $position->getFloorY() + 1, $position->getFloorZ(), $level);
			if(!$level->isInLoadedTerrain($cropPosition)){
				$level->loadChunk($cropPosition->getFloorX() >> 4, $cropPosition->getFloorZ() >> 4);
			}
			if($level->getBlockAt((int) $cropPosition->x, (int) $cropPosition->y, (int) $cropPosition->z)->getId() !== BlockLegacyIds::AIR)
				break;

			if($level->getBlock($position)->getId() === $this->farmData[$key]["id"]){
				$level->setBlock($cropPosition, BlockFactory::getInstance()->get((int) $this->farmData[$key]["id"], 0));
			}
		}
		return true;
	}

	// /////////////////////////////////////////////////////////////////////
	// //////////////////////##호박계열##///////////////////////////
	// /////////////////////////////////////////////////////////////////////
	public function updateHorizontalGrowingCrops($key, World $level, Vector3 $position){
		$cropBlock = null;

		switch($this->farmData[$key]["id"]){
			case BlockLegacyIds::PUMPKIN_STEM:
				$cropBlock = BlockFactory::getInstance()->get(BlockLegacyIds::PUMPKIN, 0);
				break;

			case BlockLegacyIds::MELON_STEM:
				$cropBlock = BlockFactory::getInstance()->get(BlockLegacyIds::MELON_BLOCK, 0);
				break;

			default:
				return true;
		}

		// 제자리성장
		$cropPosition = clone $position;
		if(!$level->isInLoadedTerrain($cropPosition)){
			$level->loadChunk($cropPosition->getFloorX() >> 4, $cropPosition->getFloorZ() >> 4);
		}
		$level->setBlock($cropPosition, $cropBlock);

		if($this->get('monitoring') && $this->get('monitoring-farm-update'))
			$this->debugMessage("§7모니터링 :: 호박 계열 농작물 성장완료 :: 현재 농작물 갯수 " . count($this->farmData) . "개");

		return true;
	}
}