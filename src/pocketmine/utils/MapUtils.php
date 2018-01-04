<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\utils;

use pocketmine\block\Block;
use pocketmine\block\Planks;
use pocketmine\block\Prismarine;
use pocketmine\block\StoneSlab;
use pocketmine\block\Stone;
use pocketmine\item\Dye;
use pocketmine\item\FilledMap as Map;
use pocketmine\Server;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\utils\Config;

class MapUtils {
	public static $BaseMapColors = [];
	public static $MapColors = [];
	public static $idConfig;
	private static $cachedMaps = [];

	public function __construct() {
		$path = Server::getInstance()->getDataPath() . "maps";
		@mkdir($path);
		$filename = "idcounts.json";
		self::$idConfig = new Config($path . "/" . $filename, Config::JSON, ["map" => 0]);
		self::$BaseMapColors = [
			new Color(0, 0, 0, 0),
			new Color(127, 178, 56),
			new Color(247, 233, 163),
			new Color(167, 167, 167),
			new Color(255, 0, 0),
			new Color(160, 160, 255),
			new Color(167, 167, 167),
			new Color(0, 124, 0),
			new Color(255, 255, 255),
			new Color(164, 168, 184),
			new Color(183, 106, 47),
			new Color(112, 112, 112),
			new Color(64, 64, 255),
			new Color(104, 83, 50),
			new Color(255, 252, 245),
			new Color(216, 127, 51),
			new Color(178, 76, 216),
			new Color(102, 153, 216),
			new Color(229, 229, 51),
			new Color(127, 204, 25),
			new Color(242, 127, 165),
			new Color(76, 76, 76),
			new Color(153, 153, 153),
			new Color(76, 127, 153),
			new Color(127, 63, 178),
			new Color(51, 76, 178),
			new Color(102, 76, 51),
			new Color(102, 127, 51),
			new Color(153, 51, 51),
			new Color(25, 25, 25),
			new Color(250, 238, 77),
			new Color(92, 219, 213),
			new Color(74, 128, 255),
			new Color(0, 217, 58),
			new Color(21, 20, 31),
			new Color(112, 2, 0),
			//new 1.8 colors
			new Color(126, 84, 48)];
		for ($i = 0; $i < count(self::$BaseMapColors); ++$i) {
			/** @var Color $bc */
			$bc = self::$BaseMapColors[$i];
			self::$MapColors[$i * 4 + 0] = new Color((int)($bc->getR() * 180.0 / 255.0 + 0.5), (int)($bc->getG() * 180.0 / 255.0 + 0.5), (int)($bc->getB() * 180.0 / 255.0 + 0.5), $bc->getA());
			self::$MapColors[$i * 4 + 1] = new Color((int)($bc->getR() * 220.0 / 255.0 + 0.5), (int)($bc->getG() * 220.0 / 255.0 + 0.5), (int)($bc->getB() * 220.0 / 255.0 + 0.5), $bc->getA());
			self::$MapColors[$i * 4 + 2] = $bc;
			self::$MapColors[$i * 4 + 3] = new Color((int)($bc->getR() * 135.0 / 255.0 + 0.5), (int)($bc->getG() * 135.0 / 255.0 + 0.5), (int)($bc->getB() * 135.0 / 255.0 + 0.5), $bc->getA());
		}
	}

	public static function getNewId(){
		$id = self::$idConfig->get("map", 0);
		$id++;
		self::$idConfig->set("map", $id);
		self::$idConfig->save();
		return $id;
	}
	
	public function getMapColors() {
		return self::$MapColors;
	}

	public static function cacheMap(Map $map){//TODO: serialize?
		self::$cachedMaps[$map->getMapId()] = $map;
	}

	public static function getCachedMap(int $uuid){
		return self::$cachedMaps[$uuid]??null;
	}

	public function getAllCachedMaps(){
		return self::$cachedMaps;
	}

	/**
	 * Returns the closest map color to a Color
	 * This will ignore alpha
	 * @param Color $color
	 * @return Color
	 */
	public function getClosestMapColor(Color $color) {
		if ($color->getA() > 128) return self::$MapColors[0];
		$index = 0;
		$best = -1;
		for ($i = 4; $i < count(self::$MapColors); $i++) {
			$distance = Color::getDistance($color, self::$MapColors[$i]);
			if ($distance < $best || $best == -1) {
				$best = $distance;
				$index = $i;
			}
		}
		return self::$MapColors[$index];
	}

	public static function distanceHSV(array $hsv1, array $hsv2) {
		return ($hsv1['v'] - $hsv2['v']) ** 2
			+ ($hsv1['s'] * cos($hsv1['h']) - $hsv2['s'] * cos($hsv2['h'])) ** 2
			+ ($hsv1['s'] * sin($hsv1['h']) - $hsv2['s'] * sin($hsv2['h'])) ** 2;
	}

	//TODO : 
	public static function exportToPDF(Map $map){
		if (!extension_loaded("gd")){
			return false;
		}
		@mkdir(Server::getInstance()->getDataPath()."maps");
		$filename = Server::getInstance()->getDataPath()."maps/map_".$map->getMapId().".png";
		$colors = $map->getColors();
		$width = $map->getWidth();
		$height = $map->getHeight();
		$img = imagecreatetruecolor($width, $height);
		#imagecolortransparent($img, imagecolorallocate($img, 0, 0, 0));
		for ($y = 0; $y < $height; ++$y) {
			for ($x = 0; $x < $width; ++$x) {
				/** @var Color $color */
				$color = $colors[$y][$x];
				imagesetpixel($img, $x, $y, imagecolorallocate($img, $color->getR(),$color->getG(),$color->getB()));
			}
		}
		return imagepng($img, $filename);
	}

	public static function exportToPNG(Map $map){
		if (!extension_loaded("gd")){
			return false;
		}
		@mkdir(Server::getInstance()->getDataPath()."maps");
		$filename = Server::getInstance()->getDataPath()."maps/map_".$map->getMapId().".png";
		$image = imagecreatetruecolor($map->getWidth(), $map->getHeight());
		imagesavealpha($image, true);
		for ($y = 0; $y < $map->getHeight(); ++$y){
			for ($x = 0; $x < $map->getWidth(); ++$x){
				$color = $map->getColorAt($x, $y);
				imagesetpixel($image, $x, $y, imagecolorallocate($image, $color->getR(), $color->getG(), $color->getB()));
			}
		}
		return imagepng($image, $filename);
	}

	public function exportToNBT(Map $map, string $name){
		$data = [];
		@mkdir(Server::getInstance()->getDataPath()."maps");
		$filename = Server::getInstance()->getDataPath()."maps/map_".$map->getMapId().".dat";
		foreach ($map->getColors() as $y => $icolors){
			foreach ($icolors as $x => $c){
				$data[$x + ($y * $map->getHeight())] = $c->toABGR();
			}
		}
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$t = new CompoundTag($name, [
					new ShortTag("width", $map->getWidth()),
					new ShortTag("height", $map->getHeight()),
					new ByteTag("scale", $map->getScale()),
					new ByteTag("fullyExplored", 1),
					new ByteTag("dimension", 0),
					new IntTag("xCenter", $map->getXOffset()),
					new IntTag("zCenter", $map->getYOffset()),
					new IntArrayTag("colors", $data),
					new ListTag("decorations", $map->getDecorations())]
			);
		$nbt->setData($t);
		file_put_contents($filename, $nbt->writeCompressed());
		return file_exists($filename);
	}

	public function loadFromNBT(string $path){
		if (!file_exists($path)) return false;
		$id = intval(str_replace(Server::getInstance()->getDataPath()."maps/map_", "", str_replace(".dat ", "", $path)));
		$map = new Map();
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->readCompressed(file_get_contents($path));
		$data = $nbt->getData();
		$map->setMapId($id);
		$map->setWidth($data->width->getValue());
		$map->setHeight($data->height->getValue());
		$map->setXOffset($data->xCenter->getValue());
		$map->setYOffset($data->zCenter->getValue());
		/** @var Color[][] */
		$colors = [];
		$colordata = $data->colors->getValue();
		for ($y = 0; $y < $map->getHeight(); ++$y){
			for ($x = 0; $x < $map->getWidth(); ++$x){
				$colors[$y][$x] = Color::fromABGR($colordata[$x + ($y * $map->getHeight())]??0);
			}
		}
		$map->setColors($colors);
		$this::exportToPNG($map);
		return $map;
	}

	public static function getBlockColor(Block $block) {
		$meta = $block->getDamage();
		$id = $block->getId();
		switch($id){
			case Block::GRASS:
			case Block::SLIME_BLOCK:
				return new Color(127, 178, 56);
				break;
			case Block::DIRT:
			case Block::FARMLAND:
			case Block::STONE && $meta == Stone::GRANITE:
			case Block::STONE && $meta == Stone::POLISHED_GRANITE:
			case Block::RED_SANDSTONE:
			case Block::RED_SANDSTONE_STAIRS:
			case Block::LOG && $meta == Planks::JUNGLE:
			case Block::PLANKS && $meta == Planks::JUNGLE:
			case Block::JUNGLE_FENCE_GATE:
			case Block::FENCE && $meta == Planks::JUNGLE:
			case Block::JUNGLE_STAIRS:
			case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::JUNGLE:
				return new Color(151, 109, 77);
				break;
			case Block::BED_BLOCK:
			case Block::COBWEB:
			//case Block::BROWN_MUSHROOM_BLOCK://todo: stem, sides only
				return new Color(199, 199, 199);
				break;
			case Block::LAVA:
			case Block::STILL_LAVA:
			case Block::TNT:
			case Block::FIRE:
			case Block::REDSTONE_BLOCK:
				return new Color(255, 0, 0);
				break;
			case Block::ICE:
			case Block::PACKED_ICE:
			case Block::FROSTED_ICE:
				return new Color(160, 160, 255);
				break;
			case Block::IRON_BLOCK:
			case Block::IRON_DOOR_BLOCK:
			case Block::IRON_TRAPDOOR:
			case Block::IRON_BARS:
			case Block::BREWING_STAND_BLOCK:
			case Block::ANVIL:
			case Block::HEAVY_WEIGHTED_PRESSURE_PLATE:
				return new Color(167, 167, 167);
				break;
			case Block::SAPLING:
			case Block::LEAVES:
			case Block::LEAVES2:
			case Block::TALL_GRASS:
			case Block::DEAD_BUSH:
			case Block::RED_FLOWER:
			case Block::DOUBLE_PLANT:
			case Block::BROWN_MUSHROOM:
			case Block::RED_MUSHROOM:
			case Block::WHEAT_BLOCK:
			case Block::CARROT_BLOCK:
			case Block::POTATO_BLOCK:
			case Block::BEETROOT_BLOCK:
			case Block::CACTUS:
			case Block::SUGARCANE_BLOCK:
			case Block::PUMPKIN_STEM:
			case Block::MELON_STEM:
			case Block::VINE:
			case Block::LILY_PAD:
				return new Color(0, 124, 0);
				break;
			case Block::WOOL && $meta == Dye::WHITE:
			case Block::CARPET && $meta == Dye::WHITE:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::WHITE:
			case Block::SNOW_LAYER:
			case Block::SNOW_BLOCK:
				return new Color(255, 255, 255);
				break;
			case Block::CLAY_BLOCK:
			case Block::MONSTER_EGG:
				return new Color(164, 168, 184);
				break;
			case Block::STONE:
			case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::STONE:
			case Block::COBBLESTONE:
			case Block::COBBLESTONE_STAIRS:
			case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::COBBLESTONE:
			case Block::COBBLESTONE_WALL:
			case Block::MOSSY_COBBLESTONE:
			case Block::STONE && $meta == Stone::ANDESITE:
			case Block::STONE && $meta == Stone::POLISHED_ANDESITE:
			case Block::BEDROCK:
			case Block::GOLD_ORE:
			case Block::IRON_ORE:
			case Block::COAL_ORE:
			case Block::LAPIS_ORE:
			case Block::DISPENSER:
			case Block::DROPPER:
			case Block::STICKY_PISTON:
			case Block::PISTON:
			case Block::PISTON_ARM_COLLISION:
			case Block::MONSTER_SPAWNER:
			case Block::DIAMOND_ORE:
			case Block::FURNACE:
			case Block::STONE_PRESSURE_PLATE:
			case Block::REDSTONE_ORE:
			case Block::STONE_BRICK:
			case Block::STONE_BRICK_STAIRS:
			case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::STONE_BRICK:
			case Block::ENDER_CHEST:
			case Block::HOPPER_BLOCK:
			case Block::GRAVEL:
			case Block::OBSERVER:
				return new Color(112, 112, 112);
				break;
			case Block::WATER:
			case Block::STILL_WATER:
				return new Color(64, 64, 255);
				break;
			case Block::WOOD && $meta == Planks::OAK:
			case Block::PLANKS && $meta == Planks::OAK:
			case Block::FENCE && $meta == Planks::OAK:
			case Block::OAK_FENCE_GATE:
			case Block::OAK_STAIRS:
			case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::OAK:
			case Block::NOTEBLOCK:
			case Block::BOOKSHELF:
			case Block::CHEST:
			case Block::TRAPPED_CHEST:
			case Block::CRAFTING_TABLE:
			case Block::WOODEN_DOOR_BLOCK:
			case Block::BIRCH_DOOR_BLOCK:
			case Block::SPRUCE_DOOR_BLOCK:
			case Block::JUNGLE_DOOR_BLOCK:
			case Block::ACACIA_DOOR_BLOCK:
			case Block::DARK_OAK_DOOR_BLOCK:
			case Block::SIGN_POST:
			case Block::WALL_BANNER:
			case Block::WALL_SIGN:
			case Block::WOODEN_PRESSURE_PLATE:
			case Block::JUKEBOX:
			case Block::WOODEN_TRAPDOOR:
			case Block::BROWN_MUSHROOM_BLOCK:
			case Block::STANDING_BANNER:
			case Block::DAYLIGHT_SENSOR:
			case Block::DAYLIGHT_SENSOR_INVERTED:
				return new Color(143, 119, 72);
				break;
			case Block::QUARTZ_BLOCK:
			case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::QUARTZ:
			case Block::QUARTZ_STAIRS:
			case Block::STONE && $meta == Stone::DIORITE:
			case Block::STONE && $meta == Stone::POLISHED_DIORITE:
			case Block::SEA_LANTERN:
				return new Color(255, 252, 245);
				break;
			case Block::WOOL && $meta == Dye::ORANGE:
			case Block::CARPET && $meta == Dye::ORANGE:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::ORANGE:
			case Block::PUMPKIN:
			case Block::JACK_O_LANTERN:
			case Block::HARDENED_CLAY:
			case Block::WOOD && $meta == Planks::ACACIA:
			case Block::PLANKS && $meta == Planks::ACACIA:
			case Block::FENCE && $meta == Planks::ACACIA:
			case Block::ACACIA_FENCE_GATE:
			case Block::ACACIA_STAIRS:
			case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::ACACIA:
				return new Color(216, 127, 51);
				break;
			case Block::WOOL && $meta == Dye::MAGENTA:
			case Block::CARPET && $meta == Dye::MAGENTA:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::MAGENTA:
			case Block::PURPUR_BLOCK:
			case Block::PURPUR_STAIRS:
				return new Color(178, 76, 216);
				break;
			case Block::WOOL && $meta == Dye::LIGHT_BLUE:
			case Block::CARPET && $meta == Dye::LIGHT_BLUE:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::LIGHT_BLUE:
				return new Color(102, 153, 216);
				break;
			case Block::WOOL && $meta == Dye::YELLOW:
			case Block::CARPET && $meta == Dye::YELLOW:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::YELLOW:
			case Block::HAY_BALE:
			case Block::SPONGE:
				return new Color(229, 229, 51);
				break;
			case Block::WOOL && $meta == Dye::LIME:
			case Block::CARPET && $meta == Dye::LIME:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::LIME:
			case Block::MELON_BLOCK:
				return new Color(229, 229, 51);
				break;
			case Block::WOOL && $meta == Dye::PINK:
			case Block::CARPET && $meta == Dye::PINK:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::PINK:
				return new Color(242, 127, 165);
				break;
			case Block::WOOL && $meta == Dye::GRAY:
			case Block::CARPET && $meta == Dye::GRAY:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::GRAY:
			case Block::CAULDRON_BLOCK:
				return new Color(76, 76, 76);
				break;
			case Block::WOOL && $meta == Dye::LIGHT_GRAY:
			case Block::CARPET && $meta == Dye::LIGHT_GRAY:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::LIGHT_GRAY:
			case Block::STRUCTURE_BLOCK:
				return new Color(153, 153, 153);
				break;
			case Block::WOOL && $meta == Dye::CYAN:
			case Block::CARPET && $meta == Dye::CYAN:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::CYAN:
			case Block::PRISMARINE && $meta == Prismarine::NORMAL:
				return new Color(76, 127, 153);
				break;
			case Block::WOOL && $meta == Dye::PURPLE:
			case Block::CARPET && $meta == Dye::PURPLE:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::PURPLE:
			case Block::MYCELIUM:
			case Block::REPEATING_COMMAND_BLOCK:
			case Block::CHORUS_PLANT:
			case Block::CHORUS_FLOWER:
				return new Color(127, 63, 178);
				break;
			case Block::WOOL && $meta == Dye::DARK_BLUE:
			case Block::CARPET && $meta == Dye::DARK_BLUE:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::DARK_BLUE:
				return new Color(51, 76, 178);
				break;
			case Block::WOOL && $meta == Dye::BROWN:
			case Block::CARPET && $meta == Dye::BROWN:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::BROWN:
			case Block::SOUL_SAND:
			case Block::WOOD && $meta == Planks::DARK_OAK:
			case Block::PLANKS && $meta == Planks::DARK_OAK:
			case Block::FENCE && $meta == Planks::DARK_OAK:
			case Block::DARK_OAK_FENCE_GATE:
			case Block::DARK_OAK_STAIRS:
			case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::DARK_OAK:
			case Block::COMMAND_BLOCK:
				return new Color(102, 76, 51);
				break;
			case Block::WOOL && $meta == Dye::GREEN:
			case Block::CARPET && $meta == Dye::GREEN:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::GREEN:
			case Block::END_PORTAL_FRAME:
			case Block::CHAIN_COMMAND_BLOCK:
				return new Color(102, 127, 51);
				break;
			case Block::WOOL && $meta == Dye::RED:
			case Block::CARPET && $meta == Dye::RED:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::RED:
			case Block::RED_MUSHROOM_BLOCK://todo: meta
			case Block::BRICKS:
			case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::BRICK:
			case Block::BRICK_STAIRS:
			case Block::ENCHANTING_TABLE:
			case Block::NETHER_WART_BLOCK:
				return new Color(153, 51, 51);
				break;
			case Block::WOOL && $meta == Dye::BLACK:
			case Block::CARPET && $meta == Dye::BLACK:
			case Block::STAINED_HARDENED_CLAY && $meta == Dye::BLACK:
			case Block::DRAGON_EGG:
			case Block::COAL_BLOCK:
			case Block::OBSIDIAN:
			case Block::END_PORTAL_BLOCK:
				return new Color(25, 25, 25);
				break;
			case Block::GOLD_BLOCK:
			case Block::LIGHT_WEIGHTED_PRESSURE_PLATE:
				return new Color(250, 238, 77);
				break;
			case Block::DIAMOND_BLOCK:
			case Block::PRISMARINE && $meta == Prismarine::DARK:
			case Block::PRISMARINE && $meta == Prismarine::BRICKS:
			case Block::BEACON:
				return new Color(92, 219, 213);
				break;
			case Block::LAPIS_BLOCK:
				return new Color(74, 128, 255);
				break;
			case Block::EMERALD_BLOCK:
				return new Color(0, 217, 58);
				break;
			case Block::SAND:
			case Block::SANDSTONE:
			case Block::SANDSTONE_STAIRS:
			case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::SANDSTONE:
			case Block::DOUBLE_STONE_SLAB && $meta == StoneSlab::SANDSTONE:
			case Block::GLOWSTONE:
			case Block::END_STONE:
			case Block::PLANKS && $meta == Planks::BIRCH:
			case Block::LOG && $meta == Planks::BIRCH:
			case Block::BIRCH_FENCE_GATE:
			case Block::FENCE && $meta = Planks::BIRCH:
			case Block::BIRCH_STAIRS:
			case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::BIRCH:
			//case Block::BROWN_MUSHROOM_BLOCK://todo: meta check for non stem inside textures
			case Block::BONE_BLOCK:
			case Block::END_BRICKS:
				return new Color(247, 233, 163);
				break;
			case Block::PODZOL:
			case Block::WOOD && $meta == Planks::SPRUCE:
			case Block::PLANKS && $meta == Planks::SPRUCE:
			case Block::FENCE && $meta == Planks::SPRUCE:
			case Block::SPRUCE_FENCE_GATE:
			case Block::SPRUCE_STAIRS:
			case Block::WOODEN_SLAB && ($meta & 0x07) == Planks::SPRUCE:
				return new Color(129, 86, 49);
				break;
			case Block::NETHERRACK:
			case Block::NETHER_QUARTZ_ORE:
			case Block::NETHER_BRICK_FENCE:
			case Block::NETHER_BRICK_BLOCK:
			case Block::RED_NETHER_BRICK:
			case Block::MAGMA:
			case Block::NETHER_BRICK_STAIRS:
			case Block::STONE_SLAB && ($meta & 0x07) == StoneSlab::NETHER_BRICK:
				return new Color(112, 2, 0);
				break;
			default:
				return new Color(0, 0, 0, 0);
		}
	}
}