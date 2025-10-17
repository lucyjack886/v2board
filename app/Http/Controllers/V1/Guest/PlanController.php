<?php

namespace App\Http\Controllers\V1\Guest;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * 公开查询计划列表 - 无需登录
     */
    public function fetch(Request $request)
    {
        if ($request->input('id')) {
            $plan = Plan::where('id', $request->input('id'))
                ->where('show', 1)
                ->first();
            if (!$plan) {
                abort(404, __('Subscription plan does not exist'));
            }
            return response([
                'data' => $plan
            ]);
        }

        $counts = PlanService::countActiveUsers();
        $plans = Plan::where('show', 1)
            ->orderBy('sort', 'ASC')
            ->get();

        // 计算容量限制
        foreach ($plans as $k => $v) {
            if ($plans[$k]->capacity_limit === NULL) continue;
            if (!isset($counts[$plans[$k]->id])) continue;
            $plans[$k]->capacity_limit = $plans[$k]->capacity_limit - $counts[$plans[$k]->id]->count;
        }

        return response([
            'data' => $plans
        ]);
    }
}
