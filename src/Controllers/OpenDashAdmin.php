<?php
namespace OpenPolice\Controllers;

use DB;
use App\Models\User;
use App\Models\OPComplaints;
use App\Models\OPDepartments;
use App\Models\OPZeditDepartments;
use App\Models\OPZeditOversight;
use App\Models\OPzVolunStatDays;

class OpenDashAdmin
{
    public $v = [];
    
    public function printDashSessGraph()
    {
        $this->v["isDash"] = true;
        $GLOBALS["SL"]->x["needsCharts"] = true;
        $GLOBALS["SL"]->pageAJAX .= '$("#1359graph").load("/dashboard/surv-1/sessions/graph-daily"); ';
        return '<div id="1359graph" class="w100" style="height: 420px;"></div>';
    }
    
    public function printDashPercCompl()
    {
        $this->v["isDash"] = true;
        $GLOBALS["SL"]->x["needsCharts"] = true;
        $GLOBALS["SL"]->pageAJAX .= '$("#1342graph").load("/dashboard/surv-1/sessions/graph-durations"); ';
        return '<div id="1342graph" class="w100" style="height: 420px;"></div><div class="p10">&nbsp;</div>'
            . '<div class="pT10"><a href="/dashboard/surv-1/sessions?refresh=1">Full Session Stats Report</a></div>';
    }
    
    public function printDashTopStats()
    {
        $this->v["isDash"] = true;
        $this->v["statRanges"] = [
            ['Last 24 Hrs', mktime(date("H")-24, date("i"), date("s"), date("n"), date("j"), date("Y"))],
            ['This Week', mktime(date("H"), 0, 0, date("n"), date("j")-7, date("Y"))],
            ['All-Time Totals', mktime(0, 0, 0, 1, 1, 1900)]
            ];
        $this->v["statusDefs"] = $GLOBALS["SL"]->def->getSet('Complaint Status');
        $this->v["dashTopStats"] = [];
        foreach ($this->v["statRanges"] as $j => $range) {
            $this->v["dashTopStats"][$j] = [];
            foreach ($this->v["statusDefs"] as $def) $this->v["dashTopStats"][$j][$def->DefID] = 0;
        }
        $chk = OPComplaints::select('ComID', 'ComPublicID', 'ComStatus', 'created_at')
            ->where('ComStatus', '>', 0)
            ->get();
        if ($chk->isNotEmpty()) {
            foreach ($chk as $i => $complaint) {
                foreach ($this->v["statRanges"] as $j => $range) {
                    if (strtotime($complaint->created_at) > $range[1]) {
                        $this->v["dashTopStats"][$j][$complaint->ComStatus]++;
                    }
                }
            }
        }
        return view('vendor.openpolice.nodes.1361-dash-top-stats', $this->v)->render();
    }
    
    public function volunDeptsRecent()
    {
        $this->v["isDash"] = true;
        $this->v["statTots"] = [];
        $statRanges = [ [
                'Last 24 Hours', 
                date("Y-m-d H:i:s", mktime(date("H")-24, date("i"), date("s"), date("n"), date("j"), date("Y")))
            ], [
                'This Week', 
                date("Y-m-d H:i:s", mktime(date("H"), 0, 0, date("n"), date("j")-7, date("Y")))
            ], [
                'All-Time Totals', 
                date("Y-m-d H:i:s", mktime(0, 0, 0, 1, 1, 1000))
            ],
        ];
        foreach ($statRanges as $i => $stat) {
            $this->v["statTots"][$i] = [ $stat[0] ];
            $this->v["statTots"][$i][] = OPZeditDepartments::distinct('ZedDeptUserID')
                ->where('ZedDeptDeptVerified', '>', $stat[1])
                ->count();
            $this->v["statTots"][$i][] = OPZeditDepartments::select('ZedDeptID')
                ->where('ZedDeptDeptVerified', '>', $stat[1])
                ->count();
            $overQry = ((strpos($stat[1], "WHERE") === false) 
                ? " WHERE `ZedOverOverType` LIKE '303'" : " AND `ZedOverOverType` LIKE '303'");
            $res = DB::select( DB::raw("SELECT SUM(`ZedOverOnlineResearch`) as `tot` FROM `OP_Zedit_Oversight` WHERE
                ZedOverOverVerified > '" . $stat[1] . "' AND `ZedOverOverType` LIKE '303'") );
            $this->v["statTots"][$i][] = $res[0]->tot;
            $res = DB::select( DB::raw("SELECT SUM(`ZedOverMadeDeptCall`) as `tot` FROM `OP_Zedit_Oversight` WHERE
                ZedOverOverVerified > '" . $stat[1] . "' AND `ZedOverOverType` LIKE '303'") );
            $this->v["statTots"][$i][] = $res[0]->tot;
            $res = DB::select( DB::raw("SELECT SUM(`ZedOverMadeIACall`) as `tot` FROM `OP_Zedit_Oversight` WHERE
                ZedOverOverVerified > '" . $stat[1] . "' AND `ZedOverOverType` LIKE '303'") );
            $this->v["statTots"][$i][] = $res[0]->tot;
            $res = DB::select( DB::raw("SELECT DISTINCT `ZedDeptDeptID` FROM `OP_Zedit_Departments` WHERE 
                ZedDeptDeptVerified > '" . $stat[1] . "'") );
            $this->v["statTots"][$i][] = (($res) ? sizeof($res) : 0);
        }
        return true;
    }
        
    public function volunDepts()
    {
        $this->volunDeptsRecent();
        $deptEdits = [];
        $recentEdits = OPZeditDepartments::take(100)
            ->orderBy('ZedDeptDeptVerified', 'desc')
            ->get();
        if ($recentEdits->isNotEmpty()) {
            foreach ($recentEdits as $i => $edit) {
                $iaEdit  = OPZeditOversight::where('ZedOverZedDeptID', $edit->ZedDeptID)
                    ->where('ZedOverOverType', 303)
                    ->first();
                $civEdit = OPZeditOversight::where('ZedOverZedDeptID', $edit->ZedDeptID)
                    ->where('ZedOverOverType', 302)
                    ->first();
                $userObj = User::find($edit->ZedDeptUserID);
                $deptEdits[] = [ ($userObj) ? $userObj->printUsername() : '', $edit, $iaEdit, $civEdit ];
            }
        }
        //echo '<pre>'; print_r($deptEdits); echo '</pre>';
        $this->v["recentEdits"] = '';
        foreach ($deptEdits as $deptEdit) {
            $this->v["recentEdits"] .= view('vendor.openpolice.volun.admPrintDeptEdit', [
                "user"     => $deptEdit[0], 
                "deptRow"  => OPDepartments::find($deptEdit[1]->ZedDeptDeptID), 
                "deptEdit" => $deptEdit[1], 
                "deptType" => $GLOBALS["SL"]->def->getVal('Department Types', $deptEdit[1]->ZedDeptType),
                "iaEdit"   => $deptEdit[2], 
                "civEdit"  => $deptEdit[3]
            ])->render();
        }
        $this->volunStatsDailyGraph();
        return view('vendor.openpolice.nodes.1351-admin-volun-edit-history', $this->v)->render();
    }
    
    public function volunStatsDailyGraph()
    {
        $this->v["isDash"] = true;
        if (!isset($this->v["statTots"])) {
            $this->volunDeptsRecent();
        }
        $this->recalcVolunStats();
        $past = 60;
        $startDate = date("Y-m-d", mktime(0, 0, 0, date("n"), date("j")-$past, date("Y")));
        $this->v["statDays"] = OPzVolunStatDays::where('VolunStatDate', '>=', $startDate)
            ->orderBy('VolunStatDate', 'asc')
            ->get();
        $this->v["axisLabels"] = [];
        foreach ($this->v["statDays"] as $i => $s) {
            if ($i%5 == 0) {
                $this->v["axisLabels"][] = date('n/j', strtotime($s->VolunStatDate));
            } else {
                $this->v["axisLabels"][] = '';
            }
        }
        $lines = [];
        $lines[0] = [
            "label"    => 'Unique Departments', 
            "brdColor" => '#2b3493', 
            "dotColor" => 'rgba(75,192,192,1)', 
            "data"     => [], 
        ];
        foreach ($this->v["statDays"] as $s) {
            $lines[0]["data"][] = $s->VolunStatDeptsUnique;
        }
        $lines[1] = [
            "label"    => 'Unique Users', 
            "brdColor" => '#63c6ff', 
            "dotColor" => 'rgba(75,192,192,1)', 
            "data"     => [], 
        ];
        foreach ($this->v["statDays"] as $s) {
            $lines[1]["data"][] = $s->VolunStatUsersUnique;
        }
        $lines[2] = [
            "label"    => 'Total Edits', 
            "brdColor" => '#c3ffe1', 
            "dotColor" => 'rgba(75,192,192,1)', 
            "data"     => [], 
        ];
        foreach ($this->v["statDays"] as $s) {
            $lines[2]["data"][] = $s->VolunStatTotalEdits;
        }
        $lines[3] = [
            "label"    => 'Total Calls', 
            "brdColor" => '#29B76F', 
            "dotColor" => 'rgba(75,192,192,1)', 
            "data"     => [], 
        ];
        foreach ($this->v["statDays"] as $s) {
            $lines[3]["data"][] = $s->VolunStatCallsTot;
        }
        $lines[4] = [
            "label"    => 'Signups', 
            "brdColor" => '#ffd2c9', 
            "dotColor" => 'rgba(75,192,192,1)', 
            "data"     => [], 
        ];
        foreach ($this->v["statDays"] as $s) {
            $lines[4]["data"][] = $s->VolunStatSignups;
        }
        $this->v["dataLines"] = '';
        foreach ($lines as $l) {
            $this->v["dataLines"] .= view('vendor.survloop.graph-data-line', $l)->render();
        }
        $volunDataGraph = view('vendor.openpolice.nodes.1351-volun-graph', $this->v)->render();
        $GLOBALS["SL"]->x["needsCharts"] = true;
        return $volunDataGraph;
    }
    
    public function volunStatsTable()
    {
        return view('vendor.openpolice.nodes.2100-volun-table', $this->v)->render();
    }
    
    public function volunStatsInitDay()
    {
        return [
            'signups'         => 0, 
            'logins'          => 0, 
            'usersUnique'     => 0, 
            'deptsUnique'     => 0, 
            'onlineResearch'  => 0, 
            'callsDept'       => 0, 
            'callsIA'         => 0, 
            'callsTot'        => 0, 
            'totalEdits'      => 0,
            'onlineResearchV' => 0, 
            'callsDeptV'      => 0, 
            'callsIAV'        => 0, 
            'callsTotV'       => 0, 
            'totalEditsV'     => 0,
            'users'           => [], 
            'depts'           => []
        ];
    }
    
    public function recalcVolunStats()
    {
        $past = 100;
        $startDate = date("Y-m-d", mktime(0, 0, 0, date("n"), date("j")-$past, date("Y")));
        $days = [];
        for ($i = 0; $i < $past; $i++) {
            $day = date("Y-m-d", mktime(0, 0, 0, date("n"), date("j")-$i, date("Y")));
            $days[$day] = $this->volunStatsInitDay();
        }
        
        $volunteers = [];
        $users = DB::table('users')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('SL_UsersRoles')
                    ->where('SL_UsersRoles.RoleUserRID', 17) // 'volunteer'
                    ->whereRaw('SL_UsersRoles.RoleUserUID = users.id');
            })
            ->get();
        if ($users->isNotEmpty()) {
            foreach ($users as $i => $u) {
                $volunteers[] = $u->id;
                if (strtotime($u->created_at) > strtotime($startDate)) {
                    $dataInd = date("Y-m-d", strtotime($u->created_at));
                    if (isset($days[$dataInd])) {
                        $days[$dataInd]["signups"]++;
                    }
                }
            }
        }
        
        $edits  = OPZeditOversight::where('OP_Zedit_Oversight.ZedOverOverType', 303)
            ->join('OP_Zedit_Departments', 'OP_Zedit_Departments.ZedDeptID', '=', 'OP_Zedit_Oversight.ZedOverZedDeptID')
            ->where('OP_Zedit_Oversight.ZedOverOverVerified', '>', date("Y-m-d", strtotime($startDate)).' 00:00:00')
            ->select('OP_Zedit_Oversight.*', 'OP_Zedit_Departments.ZedDeptUserID')
            ->get();
        if ($edits->isNotEmpty()) {
            foreach ($edits as $i => $e) {
                $day = date("Y-m-d", strtotime($e->ZedOverOverVerified));
                if (!isset($days[$day])) {
                    $days[$day] = $this->volunStatsInitDay();
                }
                $days[$day]["totalEdits"]++;
                $days[$day]["onlineResearch"] += intVal($e->ZedOverOnlineResearch);
                $days[$day]["callsDept"]      += intVal($e->ZedOverMadeDeptCall);
                $days[$day]["callsIA"]        += intVal($e->ZedOverMadeIACall);
                $days[$day]["callsTot"]       += intVal($e->ZedOverMadeDeptCall) + intVal($e->ZedOverMadeIACall);
                if (in_array($e->ZedDeptUserID, $volunteers)) {
                    $days[$day]["totalEditsV"]++;
                    $days[$day]["onlineResearchV"] += intVal($e->ZedOverOnlineResearch);
                    $days[$day]["callsDeptV"]      += intVal($e->ZedOverMadeDeptCall);
                    $days[$day]["callsIAV"]        += intVal($e->ZedOverMadeIACall);
                    $days[$day]["callsTotV"]       += intVal($e->ZedOverMadeDeptCall) + intVal($e->ZedOverMadeIACall);
                }
                if (!in_array($e->ZedDeptUserID, $days[$day]["users"])) $days[$day]["users"][] = $e->ZedDeptUserID;
                if (!in_array($e->ZedOverDeptID, $days[$day]["depts"])) $days[$day]["depts"][] = $e->ZedOverDeptID;
            }
        }
        
        OPzVolunStatDays::where('VolunStatDate', '>=', $startDate)
            ->delete();
        foreach ($days as $day => $stats) {
            $newDay = new OPzVolunStatDays;
            $newDay->VolunStatDate            = $day;
            $newDay->VolunStatSignups         = $stats["signups"];
            $newDay->VolunStatLogins          = $stats["logins"];
            $newDay->VolunStatUsersUnique     = sizeof($stats["users"]);
            $newDay->VolunStatDeptsUnique     = sizeof($stats["depts"]);
            $newDay->VolunStatOnlineResearch  = $stats["onlineResearch"];
            $newDay->VolunStatCallsDept       = $stats["callsDept"];
            $newDay->VolunStatCallsIA         = $stats["callsIA"];
            $newDay->VolunStatCallsTot        = $stats["callsTot"];
            $newDay->VolunStatTotalEdits      = $stats["totalEdits"];
            $newDay->VolunStatOnlineResearchV = $stats["onlineResearchV"];
            $newDay->VolunStatCallsDeptV      = $stats["callsDeptV"];
            $newDay->VolunStatCallsIAV        = $stats["callsIAV"];
            $newDay->VolunStatCallsTotV       = $stats["callsTotV"];
            $newDay->VolunStatTotalEditsV     = $stats["totalEditsV"];
            $newDay->save();
        }
        
        return true;
    }
    
}