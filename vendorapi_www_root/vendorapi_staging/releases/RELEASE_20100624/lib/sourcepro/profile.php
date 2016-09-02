<?php
	class SourcePro_Profile
	{
		private $metrics = array ();
		private $elapsed;
		private $overhead;
		private $last_time;
		
		function __construct ()
		{
			$this->elapsed = 0;
			$this->overhead = 0;
			$this->last_time = $this->Get_Current_Time ();
		}
		
		function Update_Time ()
		{
			$current_time = $this->Get_Current_Time ();
			$state = debug_backtrace();
			$location = (isset ($state [1]["class"]) ? $state [1]["class"] : "").(isset ($state [1]["type"]) ? $state [1]["type"] : "").(isset ($state [1]["function"]) ? $state [1]["function"] : "");
			$elapsed = ($current_time - $this->last_time);
			if (isset ($this->metrics [$state [1]["file"]][$location]))
			{
				$this->metrics [$state [1]["file"]][$location] += $elapsed;
			}
			else
			{
				$this->metrics [$state [1]["file"]][$location] = $elapsed;
			}
			
			if (isset($this->metrics [$state [1]["file"]]["__Total"]))
			{
				$this->metrics [$state [1]["file"]]["__Total"] += $elapsed;
			}
			else
			{
				$this->metrics [$state [1]["file"]]["__Total"] = $elapsed;
			}
			
			$this->elapsed += $elapsed;
			if ($location == "unknown")
			{
				$this->overhead += $elapsed;
			}
			
			$this->last_time = $current_time;
			
			return TRUE;
		}
		
		function Show_Text ()
		{
			echo "<pre>\nElapsed: ".$this->elapsed."\nOverhead: ".$this->overhead."\n\n";
			foreach ($this->metrics as $location => $data)
			{
				echo "FILE: ".$location."\n";
				echo "TOTAL: ".$data["__Total"]."\n";
				echo "OVERHEAD: ".$data ["unknown"]."\n";
				foreach ($data as $name => $time)
				{
					if ($name != "__Total" && $name != "unknown")
					{
						echo "\t".$name." => ".$time."\n";
					}
				}
				echo "\n\n";
			}
			//print_r ($this->metrics);
			echo "<\pre>";
		}
		
		function Show_Graph ()
		{
			include_once ("jpgraph/jpgraph.php");
			include_once ("jpgpaph/jpgraph_pie.php");
			
			$data = array ();
			$lbl = array ();
			
			foreach ($this->metrics as $location => $data)
			{
				$data[] = $data ["__Total"];
				
				// The label array values may have printf() formatting in them. The argument to the
				// form,at string will be the value of the slice (either the percetage or absolute
				// depending on what was specified in the SetLabelType() above.
				$lbl[] = $location."\n%0.4f";
			}
			
			// A new pie graph
			$graph = new PieGraph(400,400);
			
			// If you don't want any  border just uncomment this line
			// $graph->SetFrame(false);
			
			// Uncomment this line to add a drop shadow to the border
			// $graph->SetShadow();
			
			// Setup title
			$graph->title->Set("Profile Results - Pie Chart");
			$graph->title->SetFont(FF_TAHOMA,FS_BOLD,18);
			$graph->title->SetMargin(8); // Add a little bit more margin from the top
			
			// Create the pie plot
			$p1 = new PiePlotC($data);
			
			// Set the radius of pie (as fraction of image size)
			$p1->SetSize(0.32);
			
			// Move the center of the pie slightly to the top of the image
			$p1->SetCenter(0.5,0.45);
			
			// Label font and color setup
			$p1->value->SetFont(FF_ARIAL,FS_BOLD,12);
			$p1->value->SetColor('white');
			
			// Setup the title on the center circle
			$p1->midtitle->Set("Test mid\nRow 1\nRow 2");
			$p1->midtitle->SetFont(FF_ARIAL,FS_NORMAL,14);
			
			// Set color for mid circle
			$p1->SetMidColor('yellow');
			
			// Use percentage values in the legends values (This is also the default)
			$p1->SetLabelType(PIE_VALUE_PER);
			
			$p1->SetLabels($lbl);
			
			// Uncomment this line to remove the borders around the slices
			// $p1->ShowBorder(false);
			
			// Add drop shadow to slices
			$p1->SetShadow();
			
			// Explode all slices 15 pixels
			$p1->ExplodeAll(15);
			
			// Setup the CSIM targets
			$targ=array("piec_csimex1.php#1","piec_csimex1.php#2","piec_csimex1.php#3",
				    "piec_csimex1.php#4","piec_csimex1.php#5","piec_csimex1.php#6");
			$alts=array("val=%d","val=%d","val=%d","val=%d","val=%d","val=%d");
			$p1->SetCSIMTargets($targ,$alts);
			$p1->SetMidCSIM("piec_csimex1.php#7","Center");
			
			
			// Setup a small help text in the image
			$txt = new Text("Note: This is an example of image map. Hold\nyour mouse over the slices to see the values.\nThe URL just points back to this page");
			$txt->SetFont(FF_FONT1,FS_BOLD);
			$txt->Pos(0.5,0.97,'center','bottom');
			$txt->SetBox('yellow','black');
			$txt->SetShadow();
			$graph->AddText($txt);
			
			// Add plot to pie graph
			$graph->Add($p1);
			
			// .. and send the image on it's marry way to the browser
			$graph->StrokeCSIM();
			
			return TRUE;
		}
	
		private function Get_Current_Time ()
		{
			list ($s, $m) = explode (" ", microtime ());
			return ($s + $m);
		}
	}
	
	$sp_profile = new SourcePro_Profile ();
	register_tick_function (array ($sp_profile, "Update_Time"));
	declare(ticks=1);
?>