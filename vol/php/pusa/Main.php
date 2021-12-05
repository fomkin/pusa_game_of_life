<?php
namespace catlair;

class Main extends TWebPusa
{
    private function LoadUniverse()
    {
        session_start();
        $universe = new Universe();

        if (isset($_SESSION['universe']))
        {
             $saved_cells = $_SESSION['universe'];
             $universe->SetCells($saved_cells);
        }

        return $universe;
    }

    /* Page init */
    public function Init()
    {
        $this
          -> DOMBody()
          -> PutContent('Main.html');

	$universe = $this->LoadUniverse();

        for ($x = 0; $x < 10; $x++)
        {
            for ($y = 0; $y < 10; $y++)
            {
                $new_value = $universe->CellGet($x, $y);
                $select = 'cell-'.($x+1).'-'.($y+1);

                $this
                -> DOMBody()
                -> DOMSelect($select, TPusaCore::ID)
                -> DOMAttr([ 'fill' => ($new_value == 1) ? '#000000' : '#EEEEEE' ]);
            }
        }

	return $this
          -> DOMBody()
          -> DOMSelect( 'Button' )
          -> DOMEvent( 'Main', 'ButtonClick' )
          -> DOMParent()
          -> DOMSelect( 'cell' )
          -> DOMEvent( 'Main', 'CellClick' );
    }

    public function CellClick()
    {
	session_start();
	$universe = new Universe();

        if (isset($_SESSION['universe']))
	{
             $saved_cells = $_SESSION['universe'];
             $universe->SetCells($saved_cells);
	}

        $cell_string = $this->GetDataString('cell');
        $cell = explode('-', $cell_string);
        $x = intval($cell[0])-1;
        $y = intval($cell[1])-1;

        $new_value = ($universe->CellGet($x, $y) == 1) ? 0 : 1;
        $universe->CellSet($x, $y, $new_value);

	$_SESSION['universe'] = $universe->GetCells();

        return $this
//          -> Console(!is_null($saved_sells))
          -> DOMAttr([ 'fill' => $new_value == 1 ? '#000000' : '#EEEEEE' ]);
    }

    /* The button click callback body */
    public function ButtonClick()
    {
	session_start();
        $universe = new Universe();

        if (isset($_SESSION['universe']))
        {
             $saved_cells = $_SESSION['universe'];
             $universe->SetCells($saved_cells);
        }

        $changes = $universe->Next();
        $changes_count = count($changes);

//	return $this->Console($_SESSION['universe'])->Console($changes);

        for ($i = 0; $i < $changes_count; $i++)
        {
            $change = $changes[$i];
            $x = $change['x'];
            $y = $change['y'];
            $new_value = $change['value'];
            $select = 'cell-'.($x+1).'-'.($y+1);

            $this
              -> DOMBody()
              -> DOMSelect($select, TPusaCore::ID)
              -> DOMAttr([ 'fill' => ($new_value == 1) ? '#000000' : '#EEEEEE' ]);
        }

        $_SESSION['universe'] = $universe->GetCells();
        return $this;
    }
}

class Universe {

    private $size;
    private $cells;

    public function __construct()
    {
        $this->size = 10;
        $this->cells = array_fill(0, $this->size * $this->size, 0);
    }

    public function SetCells($cells)
    {
        $this->cells = $cells;
    }

    public function GetCells()
    {
        return $this->cells;
    }

    private function CellIndex(int $x, int $y)
    {
        $size = $this->size;
        return ($x % $size + $size) % $size + (($y % $size + $size) % $size * $size);
    }

    public function CellGet($x, $y)
    {
        $index = $this->CellIndex($x, $y);
        return $this->cells[$index];
    }

    public function CellSet($x, $y, $value)
    {
        $index = $this->CellIndex($x, $y);
        $this->cells[$index] = $value;
    }

    public function CellCountAliveNeighbors($x, $y)
    {
        return
           // Верхний ряд соседей
           $this->CellGet($x-1, $y-1) +
           $this->CellGet($x, $y-1) +
           $this->CellGet($x+1, $y-1) +
           // Левый и правый
           $this->CellGet($x-1, $y) +
           $this->CellGet($x+1, $y) +
           // Нижний ряд
           $this->CellGet($x-1, $y+1) +
           $this->CellGet($x, $y+1) +
           $this->CellGet($x+1, $y+1);
    }

    public function IsCellMustResurrect($x, $y)
    {
        return $this->CellCountAliveNeighbors($x, $y) == 3;
    }

    public function IsCellMustDie($x, $y)
    {
        $alive = $this->CellCountAliveNeighbors($x, $y);
        return $alive < 2 || $alive > 3;
    }

    public function Next()
    {
        $result = array();
        $changed = NULL;

        for ($y = 0; $y < $this->size; $y++)
        {
            for ($x = 0; $x < $this->size; $x++)
            {
                $value = $this->CellGet($x, $y);

                if ($value == 0 && $this->IsCellMustResurrect($x, $y))
                {
                    $changed = array('x' => $x, 'y' => $y, 'value' => 1);
                }
                else if ($value == 1 && $this->IsCellMustDie($x, $y))
                {
                    $changed = array('x' => $x, 'y' => $y, 'value' => 0);
                }

                if (!is_null($changed))
                {
                    array_push($result, $changed);
                    $changed = NULL;
                }
            }
        }

        for ($i = 0; $i < count($result); $i++)
        {
            $this->CellSet($result[$i]['x'], $result[$i]['y'], $result[$i]['value']);
        }

        return $result;
    }

}
