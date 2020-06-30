<?php
ini_set('log_errors','on');
ini_set('error_log','php.log');
session_start();

$monster = array();
abstract class Creature{
    protected $name;
    protected $hp;
    protected $attackMin;
    protected $attackMax;
    abstract public function sayCry();
    public function setName($str){
        $this->name = $str;
    }
    public function getName(){
        return $this->name;
    }
    public function setHp($num){
        $this->hp = $num;
    }
    public function getHp(){
        return $this->hp;
    }
    public function attack($targetobj){
        $attackPoint = mt_rand($this->attackMin,$this->attackMax);
        if(!mt_rand(0,9)){
            $attackPoint = $attackPoint * 1.5;
            $attackPoint = (int)$attackPoint;
            History::set($this->getName().'のクリティカルヒット');
        }
        $targetobj->setHp($targetobj->getHp()-$attackPoint);
        History::set($attackPoint.'ポイントのダメージ');
    }
}
//人クラス
class Human extends Creature{
    public function __construct($name,$hp,$attackMin,$attackMax){
        $this->name = $name;
        $this->hp = $hp;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }
    public function sayCry(){
        History::set('うわっ!');
    }
}
class Monster extends Creature{
    //プロパティ
    protected $img;
    //コンストラクタ
    public function __construct($name,$hp,$img,$attackMin,$attackMax){
        $this->name = $name;
        $this->hp = $hp;
        $this->img = $img;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }
    //ゲッター
    public function getImg(){
        return $this->img;
    }
    public function sayCry(){
        History::set('はう！');
    }
}
//魔法を使えるモンスタークラス
class MagicMonster extends Monster{
    //継承はしないのでprivate
    private $magicAttack;
    function __construct($name,$hp,$img,$attack,$magicAttack){
        //親コンストラクタを呼び出す
        parent::__construct($name,$hp,$img,$attack);
        $this->magicAttack = $magicAttack;
    }
    public function getMagicAttack(){
        return $this->magicAttack;
    }
    //オーバーライド
    public function attack($targetobj){
        if(!mt_rand(0,4)){
            $targetobj->setHp($targetobj->getHp()-$this->magicAttack);
        }else{
            //親クラスのattack()を呼ぶ
            parent::attack($targetobj);
        }
    }
}
//履歴管理クラス
class History{
    public static function set($str){
        if(empty($_SESSION['history'])) $_SESSION['history'] = '';
        $_SESSION['history'] = $str;
    }
    public static function clear(){
        unset($_SESSION['history']);
    }
}

//インスタンス生成
/*if(!empty($_POST['human01'])){
    error_log(print_r($_SESSION,true));
    $human = new Human('勇者',500,40,80);
}elseif(!empty($_POST['human02'])){
    $human = new Human('力士',700,30,70);
}elseif(!empty($_POST['human03'])){
    $human = new Human('魔術師',400,20,60);
};*/
$human = new Human('勇者',300,10,20);
$monsters[] = new Monster('フランケン',400,'img/monster01.png',10,20);
$monsters[] = new Monster('怪物人間',300,'img/monster02.png',10,20);    
$monsters[] = new Monster('スライム',50,'img/monster03.png',10,20);    
$monsters[] = new Monster('ミミック',200,'img/monster04.png',10,20);
    
function createMonster(){
    global $monsters;
    $monster = $monsters[mt_rand(0,3)];
    History::set($monster->getName().'が現れた!');
    $_SESSION['monster'] = $monster;
}
function createHuman(){
    global $human;
    $_SESSION['human'] = $human;
}
function init(){
    History::clear();
    History::set('初期化します');
    $_SESSION['down'] = 0;
    createHuman();
    createMonster();
}
function reinit(){
    History::clear();
    History::set('初期化します');
    $_SESSION['down'] = 0;
    createMonster();
}
function gameOver(){
    $_SESSION = array();
}

//POSTされていた場合
if(!empty($_POST)){
    $attackFlg = (!empty($_POST['attack'])) ? true : false;
    $startFlg = (!empty($_POST['human01']) || !empty($_POST['human02']) || !empty($_POST['human03']) || !empty($_POST['start'])) ? true : false;
    $restart = (!empty($_POST['restart'])) ? true : false;
    error_log('POSTされました');
    if($startFlg){
        History::set('ゲームスタート');
        init();
    }else{
        if($attackFlg){
            //モンスターに攻撃
            error_log(print_r($_SESSION,true));
            History::set($_SESSION['human']->getName().'の攻撃');
            $_SESSION['human']->attack($_SESSION['monster']);
            $_SESSION['monster']->sayCry();

            //攻撃を受ける
            History::set($_SESSION['monster']->getName().'の攻撃');
            $_SESSION['monster']->attack($_SESSION['human']);
            $_SESSION['human']->sayCry();

            //自分のHPが0以下になった場合
            if($_SESSION['human']->getHp() <= 0){
                gameOver();
            }else{
                //モンスターのhpが0以下になったら別モンスターを出現
                if($_SESSION['monster']->getHp() <= 0){
                    History::set($_SESSION['monster']->getName().'を倒した！');
                    createMonster();
                    $_SESSION['down'] += 1;
                }
            }
        }elseif($restart){
            init();
        }else{
            //逃げるを選択
            History::set('逃げた！');
            createMonster();
            createHuman();
        }
    }
    $_POST = array();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Document</title>
</head>
<body>
    <div class="wrapper">
    <?php if(empty($_SESSION)){ ?>
    <h1 class="start">キャラを選択してゲーム開始！</h1>
        <div class="inner">
            <form action="" method="post">
                <ul class="flex">
                    <li>
                        <p class="h-name">勇者</p>
                        <p class="img"><img src="img/human01.png" alt=""></p>
                        <p class="des">皆が憧れる戦士<br>攻撃力が高い</p>
                        <input class="select" type="submit" name="human01" value="このキャラにする">
                    </li>
                    <li>
                        <p class="h-name">力士</p>
                        <p class="img"><img src="img/human03.png" alt=""></p>
                        <p class="des">不動の巨漢<br>体力が高い</p>
                        <input class="select" type="submit" name="human02" value="このキャラにする">
                    </li>
                    <li>
                        <p class="h-name">旅人</p>
                        <p class="img"><img src="img/human04.png" alt=""></p>
                        <p class="des">未来を見通す力<br>一定確率で攻撃を無力化</p>
                        <input class="select" type="submit" name="human03" value="このキャラにする">
                    </li>
                </ul>
            </form>   
        </div>
    <?php }else{ ?>
        <h1 class="start">戦闘中！</h1>
        <div class="mainimg" style="background: url(img/back1.jpg) no-repeat;background-size:cover;background-position-y: -26px;">
            <div class="inner">
                <form action="" method="post">
                    <div class="flex top-wrap" style="justify-content:space-between;">
                        <div>
                           <div class="myinfo box">
                                <p class="name"><?php echo $_SESSION['human']->getName(); ?></p>
                                
                                <ul style="padding:0;">
                                    <li>
                                        <dl class="flex">
                                            <dt class="key">HP</dt>
                                            <dd class="val"><?php echo $_SESSION['human']->getHp(); ?></dd>
                                        </dl>
                                    </li>
                                    <li>
                                        <dl class="flex">
                                            <dt class="key">MP</dt>
                                            <dd class="val">100</dd>
                                        </dl>
                                    </li>
                                    <li>
                                        <dl class="flex">
                                            <dt class="key">E</dt>
                                            <dd class="val">340</dd>
                                        </dl>
                                    </li>
                                </ul>
                            </div> 
                        </div>
                        
                        <div class="monster-img"><img src="<?php echo $_SESSION['monster']->getImg(); ?>" alt=""></div>
                        <div>
                            <div class="command box">
                                <p class="name">コマンド</p>
                                
                                <ul class="" style="padding:0;">
                                    <li>
                                        <input name="attack" type="submit" value="たたかう">
                                        <input name="magic" type="submit" value="じゅもん">
                                    </li>
                                    <li>
                                        <input name="escape" type="submit" value="にげる">
                                        <input name="restart" type="submit" value="やりなおす">
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                    </div>
                    <div class="message-wrap box">
                        <p class="message"><?php echo $_SESSION['monster']->getName().'が現れた！'; ?></p>
                    </div>
                </form>   
            </div>
        </div> 
    <?php } ?>
    </div>
</body>
</html>