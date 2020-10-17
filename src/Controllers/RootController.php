<?php

namespace WeSoonNet\LaravelPlus\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RootController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $_query_params = [];

    /**
     * 返回成功消息
     *
     * @param          $message
     * @param  string  $code
     *
     * @return JsonResponse
     */
    protected function success($message = '', $code = '')
    {
        return Response()->json([
            'success' => true,
            'message' => $message,
            'code'    => $code,
        ]);
    }

    /**
     * 返回失败消息
     *
     * @param  string  $message
     * @param  string  $code
     * @param  int     $status
     *
     * @return JsonResponse
     */
    protected function failure($message = '', $code = '', $status = 200)
    {
        return Response()->json([
            'success' => false,
            'message' => $message,
            'code'    => $code,
        ], $status);
    }

    /**
     * 自动返回服务消息
     *
     * @param $response_obj
     *
     * @return JsonResponse
     */
    protected function done($response_obj)
    {
        return Response()->json([
            'success' => $response_obj->success,
            'message' => $response_obj->message,
            'code'    => $response_obj->code,
        ]);
    }

    /**
     * 返回 JSON
     *
     * @param $message
     *
     * @return JsonResponse
     */
    protected function json($message)
    {
        return Response()->json($message);
    }

    /**
     * 验证表单
     *
     * @param $data
     * @param $rules
     * @param $messages
     *
     * @return bool|string
     */
    protected function validator($data, $rules, $messages)
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails())
        {
            return $validator->errors()->first();
        }

        return true;
    }

    /**
     * 当前登录用户ID
     *
     * @param  null  $guard
     *
     * @return null
     */
    protected function id($guard = null)
    {
        return ($user = $guard ? Auth::guard($guard)->user() : Auth::user()) ? $user->id : null;
    }

    /**
     * 当前登录用户
     *
     * @return Authenticatable
     */
    protected function user()
    {
        return Auth::user();
    }

    /**
     * 插入查询参数
     *
     * @param  string  $type
     * @param  array   $query
     */
    protected function pushQuery(string $type, array $query)
    {
        $this->_query_params[$type] = $query;
    }

    /**
     * 解析URL查询参数
     *
     * @param  array     $input
     * @param  Model     $model
     * @param  callable  $cb
     * @param  bool      $return_json
     *
     * @return array|Builder[]|Collection|JsonResponse
     */
    protected function page(array $input, Model $model, callable $cb = null, $return_json = true)
    {
        // 解析查询参数
        $model = $this->parseQuery($input, $model);

        // 回调
        if ($cb)
        {
            $model = $cb($model);
        }

        // 解析分页
        $_limit = $input['limit'] ?? 0;

        if (0 == $_limit)
        {
            return $model->get();
        }

        $_limit   = (int) $_limit ?: 20;
        $paginate = $model->paginate($_limit);
        $result   = [
            'total'        => $paginate->total() ?: 0,
            'current_page' => $paginate->currentPage(),
            'last_page'    => $paginate->lastPage(),
            'data'         => $paginate->items(),
        ];

        return $return_json ? $this->json($result) : $result;
    }

    /**
     * 解析URL查询参数
     *
     * @param  array          $input  请求对象
     * @param  Model          $model  数据模型
     * @param  callable|null  $cb     回调处理
     *
     * @return Builder|Model
     */
    protected function parseQuery(array $input, Model $model, callable $cb = null)
    {
        // 回调
        if ($cb)
        {
            $model = $cb($model);
        }

        // 排序预存
        $OrderByArray = [];

        // 搜索预存
        $SearchArray = [];

        //////////////////////////////////////////////////////////////
        // 解析排序
        //////////////////////////////////////////////////////////////
        $_order       = $input['orderby'] ?? '';
        $_order_group = explode(';', $_order);
        // 追加参数
        if (isset($this->_query_params['orderby']))
        {
            foreach ($this->_query_params['orderby'] as $_item)
            {
                $_order_group[] = $_item;
            }
        }
        foreach ($_order_group as $_group)
        {
            if ($_group)
            {
                if (strpos($_group, ':'))
                {
                    [$_field, $_sort] = explode(':', $_group);
                    $_sort = (in_array(strtolower($_sort), ['asc', 'ascend'])) ? 'asc' : 'desc';
                    if (strpos($_field, '.'))
                    {
                        [$__with, $__field] = explode('.', $_field);
                        $OrderByArray[$__with][] = [$__field, $_sort];
                    }
                    else
                    {
                        $model = $model->orderBy($_field, $_sort);
                    }
                }
            }
        }

        //////////////////////////////////////////////////////////////
        // 解析搜索
        //////////////////////////////////////////////////////////////
        $_search       = $input['search'] ?? '';
        $_search_group = explode(';', $_search);
        // 追加参数
        if (isset($this->_query_params['search']))
        {
            foreach ($this->_query_params['search'] as $_item)
            {
                $_search_group[] = $_item;
            }
        }
        foreach ($_search_group as $_group)
        {
            if ($_group)
            {
                $count = substr_count($_group, ':');
                if (2 === $count)
                {
                    [$_field, $_value, $_rule] = explode(':', $_group);
                }
                else
                {
                    if (1 === $count)
                    {
                        [$_field, $_value] = explode(':', $_group);
                        $_rule = '=';
                    }
                    else
                    {
                        continue;
                    }
                }

                // 空值处理
                $_value = in_array($_value, ['null', 'NULL']) ? null : $_value;

                // 子查询
                $with_obj    = null;
                $_with_field = null;
                if (strpos($_field, '.'))
                {
                    $_with_field_array = explode('.', $_field);
                    $_with_field       = array_pop($_with_field_array);
                    $with_obj          = implode('.', $_with_field_array);
                }
                // 条件处理
                if ('=' === $_rule)
                {
                    if ($_value != '')
                    {
                        if ($with_obj)
                        {
                            $SearchArray[$with_obj][] = ['=', $_with_field, $_value];
                        }
                        else
                        {
                            $model = $model->where($_field, $_value);
                        }
                    }
                }
                else
                {
                    if ('like' === $_rule)
                    {
                        $_value = (false === strpos($_value, '%')) ? "%{$_value}%" : $_value;
                        if ($with_obj)
                        {
                            $SearchArray[$with_obj][] = ['like', $_with_field, $_value];
                        }
                        else
                        {
                            $model = $model->where($_field, 'like', $_value);
                        }
                    }
                    else
                    {
                        if ('in' === $_rule)
                        {
                            $_value = (false === strpos($_value, ',')) ? [$_value] : explode(',', $_value);
                            if ($with_obj)
                            {
                                $SearchArray[$with_obj][] = ['in', $_with_field, $_value];
                            }
                            else
                            {
                                $model = $model->whereIn($_field, $_value);
                            }
                        }
                        else
                        {
                            if ('has' === $_rule)
                            {
                                if ($_value)
                                {
                                    $_value = (false === strpos($_value, ',')) ? [$_value] : explode(',', $_value);
                                    $model  = $model->whereHas($with_obj, function ($query) use ($_with_field, $_value)
                                    {
                                        $query->where($_with_field, $_value);
                                    });
                                }
                            }
                            else
                            {
                                if ('haslike' === $_rule)
                                {
                                    if ($_value)
                                    {
                                        $_value = (false === strpos($_value, ',')) ? [$_value] : explode(',', $_value);
                                        $model  = $model->whereHas($with_obj, function ($query) use ($_with_field, $_value)
                                        {
                                            $query->where($_with_field, 'like', "%{$_value}%");
                                        });
                                    }
                                }
                                else
                                {
                                    if ('notnull' === $_rule)
                                    {
                                        if ($with_obj)
                                        {
                                            $SearchArray[$with_obj][] = ['notnull', $_with_field, $_value];
                                        }
                                        else
                                        {
                                            $model = $model->whereNotNull($_field);
                                        }
                                    }
                                    else
                                    {
                                        if ($with_obj)
                                        {
                                            $SearchArray[$with_obj][] = [$_rule, $_with_field, $_value];
                                        }
                                        else
                                        {
                                            $model = $model->where($_field, $_rule, $_value);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //////////////////////////////////////////////////////////////
        // 解析过滤
        //////////////////////////////////////////////////////////////
        $_filter       = $input['filter'] ?? '';
        $_filter_group = explode(';', $_filter);
        // 追加参数
        if (isset($this->_query_params['filter']))
        {
            foreach ($this->_query_params['filter'] as $_item)
            {
                $_filter_group[] = $_item;
            }
        }
        $_filter_group_array = [];
        foreach ($_filter_group as $item)
        {
            if ($item)
            {
                $_filter_group_array[] = $item;
            }
        }
        $model = $model->addSelect($_filter_group_array);

        //////////////////////////////////////////////////////////////
        // 解析包含关系
        //////////////////////////////////////////////////////////////
        $_include       = $input['include'] ?? '';
        $_include_group = explode(';', $_include);
        // 追加参数
        if (isset($this->_query_params['include']))
        {
            foreach ($this->_query_params['include'] as $_item)
            {
                $_include_group[] = $_item;
            }
        }
        sort($_include_group);
        foreach ($_include_group as $_group)
        {
            if ($_group)
            {
                // 有字段过滤
                if (strpos($_group, ':'))
                {
                    $_group_array = explode(':', $_group);
                    $_obj         = $_group_array[0];
                    $_fields      = $_group_array[1];
                    $_method      = 'with' . ucfirst($_group_array[2] ?? '');
                    $model        = call_user_func([$model, $_method], [
                        $_obj => function ($query) use ($_fields, $_obj, $_method, $OrderByArray, $SearchArray)
                        {
                            $model = $query;
                            if ('with' == $_method)
                            {
                                // 字段过滤
                                $_fields_array  = [];
                                $_fields_array_ = explode(',', $_fields);
                                foreach ($_fields_array_ as $_field)
                                {
                                    if ($_field)
                                    {
                                        $_fields_array[] = $_field;
                                    }
                                }
                                $model = $query->select($_fields_array);

                                // 排序
                                if (in_array($_obj, array_keys($OrderByArray)))
                                {
                                    foreach ($OrderByArray[$_obj] as $_orderby)
                                    {
                                        [$k, $v] = $_orderby;
                                        $model = $query->orderBy($k, $v);
                                    }
                                }
                            }
                            if (in_array($_obj, array_keys($SearchArray)))
                            {
                                foreach ($SearchArray[$_obj] as $_search)
                                {
                                    [$_r, $_f, $_v] = $_search;
                                    if ('=' == $_r)
                                    {
                                        $model = $model->where($_f, $_v);
                                    }
                                    else
                                    {
                                        if ('like' == $_r)
                                        {
                                            $model = $model->where($_f, 'like', $_v);
                                        }
                                        else
                                        {
                                            if ('in' == $_r)
                                            {
                                                $model = $model->whereIn($_f, $_v);
                                            }
                                            else
                                            {
                                                if ('notnull' == $_r)
                                                {
                                                    $model = $model->whereNotNull($_f);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        },
                    ]);
                }
                else
                {
                    if (in_array($_group, array_keys($SearchArray)))
                    {
                        foreach ($SearchArray[$_group] as $_search)
                        {
                            $model = $model->with([
                                $_group => function ($query) use ($_search)
                                {
                                    [$_r, $_f, $_v] = $_search;
                                    if ('=' == $_r)
                                    {
                                        $query->where($_f, $_v);
                                    }
                                    else
                                    {
                                        if ('like' == $_r)
                                        {
                                            $query->where($_f, 'like', $_v);
                                        }
                                        else
                                        {
                                            if ('in' == $_r)
                                            {
                                                $query->whereIn($_f, $_v);
                                            }
                                            else
                                            {
                                                if ('notnull' == $_r)
                                                {
                                                    $query->whereNotNull($_f);
                                                }
                                            }
                                        }
                                    }
                                },
                            ]);
                        }
                    }
                    else
                    {
                        $model = $model->with($_group);
                        if (in_array($_group, array_keys($OrderByArray)))
                        {
                            foreach ($OrderByArray[$_group] as $_orderby)
                            {
                                [$k, $v] = $_orderby;
                                $model = $model->orderBy($k, $v);
                            }
                        }
                    }
                }
            }
        }

        //////////////////////////////////////////////////////////////
        // 解析获取数量
        //////////////////////////////////////////////////////////////
        if ($_take = ($input['take'] ?? null))
        {
            if (is_numeric($_take))
            {
                $model = $model->take($_take);
            }
        }

        //////////////////////////////////////////////////////////////
        // 解析地理距离
        //////////////////////////////////////////////////////////////
        if ($_distance = ($input['distance'] ?? null))
        {
            $distance = explode(';', $_distance);
            if (2 === count($distance) && !empty($distance[0]) && !empty($distance[1]))
            {
                $model = $model->distance($distance[1], $distance[0]);
            }
        }

        //////////////////////////////////////////////////////////////
        // 解析地理范围
        //////////////////////////////////////////////////////////////
        if ($_geofence = ($input['geofence'] ?? null))
        {
            $geofence = explode(';', $_geofence);
            if (4 === count($geofence))
            {
                $model = $model->geofence($geofence[1], $geofence[0], $geofence[2], $geofence[3]);
            }
        }

        //////////////////////////////////////////////////////////////
        // 其他辅助查询
        //////////////////////////////////////////////////////////////
        if ($_expand = ($input['expend'] ?? null))
        {
            $_expands = explode(';', $_expand);
            foreach ($_expands as $expand)
            {
                // 随机查询
                if ('random' === $expand)
                {
                    $model = $model->inRandomOrder();
                }
            }
        }

        return $model;
    }
}
