<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected array $allowedRoles = ['admin', 'petugas'];
    private $messageAll = 'Success to Fetch All Datas';

    public function index(Request $request)
    {
        try {
            $query = User::query();

            if ($request->filled('search')) {
                $like = $this->likeOperator();
                $query->where(function ($q) use ($request, $like) {
                    $q->where('nama', $like, '%' . $request->search . '%')
                      ->orWhere('username', $like, '%' . $request->search . '%');
                });
            }

            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $data = $query->orderBy('urutan', 'asc')->get();

            return response()->json(['data' => $data, 'message' => $this->messageAll], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'nama'      => 'required|string|max:255',
                'username'  => 'required|string|max:100|unique:users,username',
                'password'  => 'required|string|min:8|confirmed',
                'role'      => 'required|in:' . implode(',', $this->allowedRoles),
                'is_active' => 'boolean',
            ]);

            $user = User::create([
                'nama'          => $validated['nama'],
                'username'      => $validated['username'],
                'password_hash' => Hash::make($validated['password']),
                'role'          => $validated['role'],
                'urutan'        => User::max('urutan') + 1,
                'is_active'     => $validated['is_active'] ?? true,
            ]);
            DB::commit();
            return response()->json([
                'message' => 'User berhasil ditambahkan.',
                'data'    => $user->only('id', 'nama', 'username', 'role', 'urutan', 'is_active'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            return response()->json([
                'message' => "Ada kesalahan",
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function show(User $user)
    {
        return response()->json(
            $user->only('id', 'nama', 'username', 'role', 'urutan', 'is_active', 'created_at')
        );
    }

    public function update(Request $request, User $user)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'nama'      => 'sometimes|required|string|max:255',
                'username'  => 'sometimes|required|string|max:100|unique:users,username,' . $user->id,
                'password'  => 'sometimes|nullable|string|min:8|confirmed',
                'role'      => 'sometimes|required|in:' . implode(',', $this->allowedRoles),
                'urutan'    => 'sometimes|required|integer',
                'is_active' => 'boolean',
            ]);

            if ($request->filled('password')) {
                $validated['password_hash'] = Hash::make($request->password);
                unset($validated['password']);
                unset($validated['password_confirmation']);
            }

            $user->update($validated);
            DB::commit();
            return response()->json([
                'message' => 'User berhasil diperbarui.',
                'data'    => $user->only('id', 'nama', 'username', 'role', 'urutan', 'is_active'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            return response()->json([
                'message' => 'Something went wrong',
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password_hash' => Hash::make($request->password),
        ]);

        $user->tokens()->delete();

        return response()->json(['message' => 'Password berhasil direset. Semua sesi aktif telah dicabut.']);
    }

    public function destroy(User $user)
    {
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }

        DB::beginTransaction();
        try {
            $user->tokens()->delete();
            $user->delete();

            // Resequence
            $items = User::orderBy('urutan', 'asc')->get();
            foreach ($items as $index => $item) {
                $item->update(['urutan' => $index + 1]);
            }

            DB::commit();
            return response()->json(['message' => 'User berhasil dihapus dan urutan diperbarui.']);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
