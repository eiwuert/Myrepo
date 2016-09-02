<?php
	
	class Display_Graph
	{
		
		protected $sets = array();
		
		protected $axis_x;
		protected $axis_y;
		protected $legend;
		protected $chart;
		protected $border;
		
		protected $height;
		protected $width;
		
		protected $margin_top = 40;
		protected $margin_bottom = 40;
		protected $margin_left = 40;
		protected $margin_right = 40;
		
		public function __construct()
		{
			$this->axis_x = new Object_Axis();
			$this->axis_y = new Object_Axis();
			$this->legend = new Object_Legend();
		}
		
		public function Render(&$report, $file = NULL)
		{
		}
		
	}
	
	class Display_JPGraph extends Graph
	{
		
		public function Render(&$report, $file = NULL)
		{
			
			$max_y = (is_numeric($this->axis_y->Maximum())) ? $this->axis_y->Maximum() : 0;
			$min_y = (is_numeric($this->axis_y->Minimum())) ? $this->axis_y->Minimum() : 0;
			$max_x = (is_numeric($this->axis_x->Maximum())) ? $this->axis_x->Maximum() : 0;
			$min_x = (is_numeric($this->axis_x->Minimum())) ? $this->axis_x->Minimum() : 0;
			
			$graph = new Graph($this->width, $this->height, '');
			$graph->SetMargin($this->margin_left, $this->margin_right, $this->margin_top, $this->margin_bottom);
			$graph->SetScale("intint", $min_y, $max_y, $min_x, $max_x);
			
			$graph->xgrid->SetColor($this->axis_x->Grid()->Color(), $this->axis_x->Grid()->Color());
			$graph->ygrid->SetColor('#000000@.8', '#222222@.8');
			$graph->yaxis->SetColor('#222222', '#ffffff@.5');
			
			$show = $this->axis_x->Grid()->Visible();
			$graph->xgrid->Show($show, $show);
			$graph->xaxis->SetColor('#222222', '#ffffff@.5');
			
			
			$graph->legend->SetFont(FF_VERDANA, FS_NORMAL, 10);
			$graph->SetMarginColor('#4c5e6f');
			$graph->SetFrame(FALSE);
			
			// set the color for the plot area
			$graph->SetColor($this->Color($this->chart));
			
			if ($this->border)
			{
				$graph->SetBox(TRUE, '#222222', 3);
			}
			
			$graph->xaxis->SetTickLabels($labels);
			$graph->xaxis->SetFont(FF_VERDANA, FS_NORMAL, 8);
			$graph->yaxis->SetFont(FF_VERDANA, FS_NORMAL, 8);
			$graph->yaxis->HideFirstTickLabel();
			$graph->xaxis->scale->ticks->Set(60, 15);
			
			foreach ($this->sources as $key=>&$source)
			{
				
				$data = array_fill(0, count($data_x), NULL);
				$source->Fill($intervals, $data);
				
				// create the plot
				$lineplot = new LinePlot($data);
				$lineplot->SetColor($this->colors[$key]);
				$lineplot->SetLegend($this->titles[$key]);
				$graph->Add($lineplot);
				unset($lineplot);
				
			}
			
			$graph->Stroke();
		}
		
		protected function Color(Drawn_Object $object)
		{
			$color = $object->Color().'@'.($object->alpha / 100);
			return $color;
		}
		
	}
	
	class SWF_Graph extends Graph
	{
		
		protected function Build_Axis(Object_Axis $axis)
		{
			
			$chart = array();
			$this->Build_Text($axis->Text(), $chart);
			
			if (!is_numeric($min = $axis->Minimum())) $min = FALSE;
			if (!is_numeric($max = $axis->Maximum())) $max = FALSE;
			
			if ($min && $max)
			{
				
				$chart['min'] = $min;
				$chart['max'] = $max;
				
				if (is_numeric($axis->Minor_Step()))
				{
					$step = (($max - $min) / $axis->Minor_Step());
					$chart['steps'] = $step;
				}
				
			}
			elseif (is_numeric($axis->Minor_Step()))
			{
				$chart['steps'] = $axis->Minor_Step();
			}
			
			if ($axis->Hide_First() === TRUE) $a['show_min'] = FALSE;
			
			return $a;
			
		}
		
		protected function Build_Ticks(Object_Axis $axis_x, Object_Axis $axis_y)
		{
			
			$chart = array();
			
			$chart['value_ticks'] = ($axis_x->Ticks()->Visible());
			$chart['category_ticks'] = ($axis_y->Ticks()->Visible());
			
			switch ($axis_x->Ticks()->Side())
			{
				case Object_Ticks::SIDE_INSIDE: $side = 'inside'; break;
				case Object_Ticks::SIDE_OUTSIDE: $side = 'outside'; break;
			}
			
			if (is_numeric($line->Weight())) $chart['minor_thickness'] = $line->Weight();
			
		}
		
		protected function Build_Grid(Object_Axis $axis)
		{
			
			$chart = array();
			$this->Build_Line($axis->Grid(), $chart);
			
			return $chart;
			
		}
		
		protected function Build_Legend(Object_Legend $legend)
		{
			
			
			$chart['legend_rect'] = array(
				'x'=>($this->width - 240),
				'y'=>120,
				'width'=>$legend->Width(),
				'height'=>$legend->Height(),
				'margin'=>$legend->Margin(),
			);
			
			$this->Build_Object($legend, $chart, 'fill_');
			$this->Build_Line($legend->Border(), $chart, 'line_');
			
			$chart['legend_label'] = array( 'size'=>16 );
			
		}
		
		protected function Build_Object(Object $object, &$chart, $prefix = '')
		{
			
			if ($object->Color()) $chart[$prefix.'color'] = $this->Color($object->Color());
			if (is_numeric($object->Alpha())) $chart[$prefix.'alpha'] = $object->Alpha();
			
		}
		
		protected function Build_Line(Object_Line $line, &$chart, $prefix = '')
		{
			
			$this->Build_Object($line, $chart, $prefix);
			
			if ($line->Style()) $chart[$prefix.'type'] = $line->Style();
			if (is_numeric($line->Weight())) $chart[$prefix.'thickness'] = $line->Weight();
			
		}
		
		protected function Build_Text(Object_Text $text, &$chart, $prefix = '')
		{
			
			// build object stuff first
			$this->Build_Object($text, $chart, $prefix);
			
			if ($text->Font()) $chart[$prefix.'font'] = $text->Font();
			if (is_numeric($text->Size())) $chart[$prefix.'size'] = $text->Size();
			if ($text->Style() === Object_Text::STYLE_BOLD) $chart[$prefix.'bold'] = TRUE;
			
		}
		
		public function Render(&$report, $file = NULL)
		{
			
			$chart['axis_category'] = $this->Build_Axis($this->axis_x);
			$chart['chart_grid_v'] = $this->Build_Axis($this->axis_x);
			
			$chart['axis_value'] = $this->Build_Axis($this->axis_y);
			$chart['chart_grid_h'] = $this->Build_Axis($this->axis_y);
			
			$chart['axis_ticks'] = array(
				'value_ticks'=>true,
				'category_ticks'=>true,
				'major_thickness'=>2,
				'minor_thickness'=>1,
				'minor_count'=>4,
				'major_color'=>"000000",
				'minor_color'=>"000000",
				'position'=>"outside"
			);
			
			$chart['chart_border'] = array(
				'color'=>"000000",
				'top_thickness'=>2,
				'bottom_thickness'=>2,
				'left_thickness'=>2,
				'right_thickness'=>2
			);
			
			$chart['chart_pref'] = array(
				'line_thickness'=>2,
				'point_shape'=>"none",
				'fill_shape'=>FALSE
			);
			
			$chart['chart_rect'] = array(
				'x'=>$this->margin_right,
				'y'=>$this->margin_top,
				'width'=>($this->width - ($this->margin_right + $this->margin_left)),
				'height'=>($this->height - ($this->margin_top + $this->margin_bottom)),
				'positive_color'=>$this->Color($this->chart->Color()),
				'positive_alpha'=>$this->chart->Alpha(),
				'negative_color'=>$this->Color($this->chart->Color()),
				'negative_alpha'=>$this->chart->Alpha(),
			);
			
			$chart['chart_type'] = "Line";
			$chart['chart_value'] = array (
				'prefix'=>"",
				'suffix'=>"%",
				'decimals'=>0,
				'separator'=>"",
				'position'=>"cursor",
				'hide_zero'=>true,
				'as_percentage'=>false,
				'font'=>"arial",
				'bold'=>true,
				'size'=>12,
				'color'=>"ffffff",
				'alpha'=>75
			);
			
			if ($this->title)
			{
				$chart['draw'] = array (
					array (
						'type'=>"text",
						'color'=>"ffffff",
						'font'=>"arial",
						'alpha'=>70,
						'rotation'=>0,
						'layer'=>'background',
						'bold'=>true,
						'size'=>50,
						'x'=>0,
						'y'=>($this->margin),
						'width'=>$this->width,
						'height'=>150,
						'text'=>$this->title,
						'h_align'=>"center",
						'v_align'=>"top"
					),
				);
			}
			
			$chart['legend_rect'] = array(
				'x'=>($this->width - 240),
				'y'=>120,
				'width'=>100,
				'height'=>50,
				'margin'=>10,
			);
			
			$chart['legend_label'] = array( 'size'=>16 );
			$chart['series_color'] = $this->colors;
			$chart['live_update'] = array(
				'url' => 'http://report1.clkonline.com.ds38.tss/new/test.php?'.time(),
				'delay' => 30
			);
			
			$chart[ 'chart_data' ] = array();
			$data = array('') + array_values($labels);
			$chart['chart_data'][] = $data;
			
			foreach ($this->sources as $key=>&$source)
			{
				
				$data = array_fill(0, count($data_x), NULL);
				$source->Fill($intervals, $data);
				
				array_unshift($data, $this->titles[$key]);
				$chart['chart_data'][] = $data;
				unset($data);
				
			}
			
			SendChartData ( $chart );
			die();
			
		}
		
		protected function Color($color)
		{
			
			if (is_string($color) && ($color{0} == '#')) $color = substr($color, 1);
			return $color;
			
		}
		
	}
	
	class Drawn_Object
	{
		
		protected $color = '#000000';
		protected $alpha = 100;
		
		public function Color($color = NULL)
		{
			
			if (is_array($color))
			{
				
				if (count($color) === 3)
				{
					$hex = '#';
					foreach ($color as $int) $hex .= dechex((int)$int);
				}
				else
				{
					$color = NULL;
				}
				
			}
			
			if (is_string($color))
			{
				$this->color = $color;
			}
			
			return $this->color;
			
		}
		
		public function Alpha($alpha = NULL)
		{
			
			if (is_numeric($alpha))
			{
				
				$alpha = (int)$alpha;
				if ($alpha > 100) $alpha = 100;
				if ($alpha < 0) $alpha = 0;
				
				$this->alpha = $alpha;
				
			}
			
			return $this->alpha;
			
		}
		
	}
	
	class Object_Text extends Drawn_Object
	{
		
		const STYLE_NORMAL = 'NORMAL';
		const STYLE_BOLD = 'BOLD';
		const STYLE_ITALIC = 'ITALIC';
		
		protected $font_name;
		protected $font_size = 12;
		protected $font_style = self::STYLE_NORMAL;
		
		public function Font($name = NULL)
		{
			if ($name !== NULL) $this->name = $name;
			return $this->name;
		}
		
		public function Size($size = NULL)
		{
			if (is_numeric($size) && ($size > 0)) $this->size = $size;
			return $this->size;
		}
		
		public function Style($style = NULL)
		{
			if ($style !== NULL) $this->style = $style;
			return $this->style;
		}
		
	}
	
	class Object_Line extends Drawn_Object
	{
		
		const STYLE_SOLID = 'SOLID';
		const STYLE_DOTTED = 'DOTTED';
		const STYLE_DASHED = 'DASHED';
		
		protected $weight = 1;
		protected $style = self::STYLE_SOLID;
		
		public function Weight($weight = NULL)
		{
			if (is_numeric($weight) && ($weight > 0)) $this->weight = $weight;
			return $this->weight;
		}
		
		public function Style($style = NULL)
		{
			if ($style !== NULL) $this->style = $style;
			return $this->style;
		}
		
	}
	
	class Object_Grid extends Object_Line
	{
		
		protected $visible = TRUE;
		
		public function Visible($visible = NULL)
		{
			if (is_bool($visible)) $this->visible = $visible;
			return $this->visible;
		}
		
	}
	
	class Object_Ticks extends Drawn_Object
	{
		
		const SIDE_OUTSIDE = 'OUTSIDE';
		const SIDE_INSIDE = 'INSIDE';
		
		protected $visible = TRUE;
		protected $side = self::SIDE_OUTSIDE;
		protected $weight = 1;
		
		public function Visible($visible = NULL)
		{
			if (is_bool($visible)) $this->visible = $visible;
			return $this->visible;
		}
		
		public function Weight($weight = NULL)
		{
			if (is_numeric($weight) && ($weight > 0)) $this->weight = $weight;
			return $this->weight;
		}
		
		public function Side($side = NULL)
		{
			
			if ($side !== NULL) $this->side = $side;
			return $this->side;
			
		}
		
	}
	
	class Object_Axis extends Drawn_Object
	{
		
		protected $text;
		protected $grid;
		protected $ticks;
		
		protected $maximum;
		protected $minimum;
		protected $major_step = 1;
		protected $minor_step = 1;
		
		protected $hide_first = FALSE;
		protected $hide_last = FALSE;
		
		public function __construct()
		{
			$this->text = new Object_Text();
			$this->grid = new Object_Grid();
			$this->ticks = new Object_Ticks();
		}
		
		public function Text($text = NULL)
		{
			if ($text instanceof Object_Text) $this->text = $text;
			return $this->text;
		}
		
		public function Grid($grid = NULL)
		{
			if ($grid instanceof Object_Grid) $this->grid = $grid;
			return $this->grid;
		}
		
		public function Ticks($ticks = NULL)
		{
			if ($ticks instanceof Object_Ticks) $this->ticks = $ticks;
			return $this->ticks;
		}
		
		public function Maximum($max = NULL)
		{
			if (is_numeric($max) && ($max > 0)) $this->max = $max;
			return $this->max;
		}
		
		public function Minimum($min = NULL)
		{
			if (is_numeric($min) && ($min > 0)) $this->min = $min;
			return $this->min;
		}
		
		public function Major_Step($step = NULL)
		{
			if (is_numeric($step) && ($step > 0)) $this->major_step = $step;
			return $this->major_step;
		}
		
		public function Minor_Step($step = NULL)
		{
			if (is_numeric($step) && ($step > 0)) $this->minor_step = $step;
			return $this->minor_step;
		}
		
		public function Hide_First($hide = NULL)
		{
			if (is_bool($hide)) $this->hide_first = $hide;
			return $this->hide_first;
		}
		
		public function Hide_Last($hide = NULL)
		{
			if (is_bool($hide)) $this->hide_last = $hide;
			return $this->hide_last;
		}
		
	}
	
	class Object_Legend extends Drawn_Object
	{
		
		protected $text;
		protected $margin;
		protected $border;
		
		public function __construct()
		{
			$this->text = new Object_Text();
			$this->border = new Object_Line();
		}
		
		public function Text($text = NULL)
		{
			if ($text instanceof Object_Text) $this->text = $text;
			return $this->text;
		}
		
		public function Border($border = NULL)
		{
			if ($border instanceof Object_Line) $this->border = $border;
			return $this->border;
		}
		
	}
	
?>