<?php

namespace App\Http\Controllers;

use App\Careerlog;
use App\Http\Requests;
use App\Upazilla;
use DateTime;
use Illuminate\Http\Request;

use App\User;
use App\About;
use App\Slider;
use App\Album;
use App\Albumphoto;
use App\Event;
use App\Notice;
use App\Basicinfo;
use App\Formmessage;
use App\Payment;
use App\Paymentreceipt;
use App\Faq;
use App\Committee;
use App\Donor;
use App\Donation;
use App\Branch;
use App\Branchpayment;
use App\Tempmemdata;
use App\Position;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Auth;
use Image;
use File;
use Session, Config;
use Hash;
use PDF;
use Illuminate\Pagination\LengthAwarePaginator;

// use BanglaDate;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('auth');
        $this->middleware('admin')->except('getBlogs', 'getProfile', 'getPaymentPage', 'getSingleMember', 'getSelfPaymentPage', 'storeSelfPayment', 'getBulkPaymentPage', 'searchMemberForBulkPaymentAPI', 'findMemberForBulkPaymentAPI', 'storeBulkPayment', 'getMemberTransactionSummary', 'getMemberUserManual', 'getMemberChangePassword', 'memberChangePassword', 'downloadMemberPaymentPDF', 'downloadMemberCompletePDF', 'updateMemberProfile', 'getApplications', 'searchApplicationAPI', 'getDefectiveApplications', 'searchDefectiveApplicationAPI', 'getMembers', 'searchMemberAPI2', 'getMembersForAll', 'searchMemberAPI3', 'searchMemberForBulkPaymentSingleAPI');
    }

    private function addToAdminLog($performedOn, $type, $description, $properties)
    {
        activity()
            ->useLog($type)
            ->performedOn($performedOn)
            ->causedBy(Auth::user())
            ->withProperties($properties)
            ->log($description);
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $about = About::where('type', 'about')->get()->first();
        // $whoweare = About::where('type', 'whoweare')->get()->first();
        // $whatwedo = About::where('type', 'whatwedo')->get()->first();
        // $ataglance = About::where('type', 'ataglance')->get()->first();
        // $membership = About::where('type', 'membership')->get()->first();
        // $basicinfo = Basicinfo::where('id', 1)->first();
        $totalpending = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', '=', 0)
            ->where('is_archieved', '=', 0)
            // ->where(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), "=", Carbon::now()->format('Y-m'))
            // ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->first();
        $totalapproved = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', '=', 1)
            ->where('is_archieved', '=', 0)
            // ->where(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), "=", Carbon::now()->format('Y-m'))
            // ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->first();
        $registeredmember = User::where('activation_status', 1)
            ->where('role_type', '!=', 'admin')
            ->count();

        $pendingpayments = Payment::where('payment_status', 0)
                ->where('is_archieved', 0)
                ->count()
            +
            User::where('activation_status', 0)
                ->orWhere('activation_status', 202)
                ->count();

        $successfullpayments = Payment::where('payment_status', 1)->count();

        $totalapplicationpending = DB::table('users')
            ->select(DB::raw('SUM(application_payment_amount) as totalamount'))
            ->where('activation_status', '=', 0)
            // ->where(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), "=", Carbon::now()->format('Y-m'))
            // ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->first();

        $totaldonation = DB::table('donations')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->first();
        $totaldonors = Donor::count();

        $totalbranchpayment = DB::table('branchpayments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->first();
        $totalbranches = Branch::count();

        $lastsixmembers = User::where('activation_status', 1)
            ->where('role', 'member')
            ->orderBy('updated_at', 'desc')
            ->take(6)->get();

        $lastsixmemberstest = User::where('activation_status', 1)
            ->where('role', 'member')
            ->orderBy('updated_at', 'desc')
            ->take(6)->get()->toJson();

        $lastsevenmonthscollection = DB::table('payments')
            ->select('created_at', DB::raw('SUM(amount) as totalamount'))
            ->where('is_archieved', '=', 0)
            ->where('payment_status', '=', 1)
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('created_at', 'DESC')
            ->take(12)
            ->get();
        $monthsforchartc = [];
        foreach ($lastsevenmonthscollection as $key => $months) {
            $monthsforchartc[] = date_format(date_create($months->created_at), "M Y");
        }
        $monthsforchartc = json_encode(array_reverse($monthsforchartc));

        $totalsforchartc = [];
        foreach ($lastsevenmonthscollection as $key => $months) {
            $totalsforchartc[] = $months->totalamount;
        }
        $totalsforchartc = json_encode(array_reverse($totalsforchartc));

        // $bangla_date = new BanglaDate(strtotime(date('d-m-Y')), 0);

        // $bangla_output = $bangla_date->get_date();

        // $datebangla =  $bangla_output[1] . ' ' . $bangla_output[0]  . ', ' . $bangla_output[2];

        return view('dashboard.index')
            ->withTotalpending($totalpending)
            ->withTotalapproved($totalapproved)
            ->withRegisteredmember($registeredmember)
            ->withPendingpayments($pendingpayments)
            ->withSuccessfullpayments($successfullpayments)
            ->withLastsixmembers($lastsixmembers)
            ->withMonthsforchartc($monthsforchartc)
            ->withTotalsforchartc($totalsforchartc)
            ->withTotaldonation($totaldonation)
            ->withTotaldonors($totaldonors)
            ->withTotalbranchpayment($totalbranchpayment)
            ->withTotalbranches($totalbranches)
            ->withTotalapplicationpending($totalapplicationpending)
            ->withLastsixmemberstest($lastsixmemberstest);
    }

    public function getAdmins()
    {
        $superadmins = User::where('role', 'admin')
            ->whereNotIn('email', ['mannan@cvcsbd.com', 'dataentry@cvcsbd.com']) // jei email gula deoa hobe tader k dekhabe na!!!
            ->where('role_type', 'admin')
            ->paginate(10);

        $admins = User::where('role', 'admin')
            ->where('role_type', 'manager')
            ->paginate(10);
        // $whoweare = About::where('type', 'whoweare')->get()->first();
        // $whatwedo = About::where('type', 'whatwedo')->get()->first();
        // $ataglance = About::where('type', 'ataglance')->get()->first();
        // $membership = About::where('type', 'membership')->get()->first();
        // $basicinfo = Basicinfo::where('id', 1)->first();

        return view('dashboard.adminsandothers.admins')
            ->withSuperadmins($superadmins)
            ->withAdmins($admins);
    }

    public function getCreateAdmin()
    {
        return view('dashboard.adminsandothers.createadmin');
    }

    public function searchMemberForAdminAPI(Request $request)
    {
        // $response = User::select('name_bangla','member_id', 'image')
        //                 ->where('member_id', 'like', '%' . $request->searchentry . '%')
        //                 ->orWhere('name_bangla', 'like', '%' . $request->searchentry . '%')
        //                 ->orWhere('name', 'like', '%' . $request->searchentry . '%')
        //                 ->orWhere('mobile', 'like', '%' . $request->searchentry . '%')
        //                 ->get();
        $response = User::select('name_bangla', 'member_id', 'mobile')
            ->where('role_type', '!=', 'admin')
            ->where('role_type', '!=', 'manager')
            ->where('activation_status', 1)
            ->orderBy('id', 'desc')->get();

        return $response;
    }

    public function addAdmin(Request $request)
    {
        $this->validate($request, array(
            'member_id' => 'required'
        ));

        $member = User::where('member_id', $request->member_id)->first();
        $member->role = 'admin';
        $member->role_type = 'manager';
        $member->save();
        $this->addToAdminLog($member, 'add_admin', 'নতুন এডমিন যোগদান', []);


        Session::flash('success', 'সফলভাবে অ্যাডমিন বানানো হয়েছে!');
        return redirect()->route('dashboard.admins');
    }

    public function removeAdmin(Request $request, $id)
    {
        $member = User::find($id);
        $member->role = 'member';
        $member->role_type = 'member';
        $member->save();
        $this->addToAdminLog($member, 'remove_admin', 'এডমিন অব্যহতি', []);

        Session::flash('success', 'সফলভাবে অ্যাডমিন থেকে অব্যহতি দেওয়া হয়েছে!');
        return redirect()->route('dashboard.admins');
    }

    public function getBulkPayers()
    {
        $bulkpayers = User::where('role_type', 'bulkpayer')->paginate(10);

        return view('dashboard.adminsandothers.bulkpayers')->withBulkpayers($bulkpayers);
    }

    public function getCreateBulkPayer()
    {
        return view('dashboard.adminsandothers.createbulkpayer');
    }

    public function searchMemberForBulkPayerAPI(Request $request)
    {
        $response = User::select('name_bangla', 'member_id', 'mobile')
            ->where('role_type', 'member')
            ->where('activation_status', 1)
            ->orderBy('id', 'desc')->get();

        return $response;
    }

    public function addBulkPayer(Request $request)
    {
        $this->validate($request, array(
            'member_id' => 'required'
        ));

        $member = User::where('member_id', $request->member_id)->first();
        $member->role_type = 'bulkpayer';
        $member->save();
        $this->addToAdminLog($member, 'add_bulkpayer', 'একাধিক পরিশোধকারী যোগদান', []);

        Session::flash('success', 'সফলভাবে একাধিক পরিশোধকারী বানানো হয়েছে!');
        return redirect()->route('dashboard.bulkpayers');
    }

    public function removeBulkPayer(Request $request, $id)
    {
        $member = User::find($id);
        $member->role_type = 'member';
        $member->save();
        $this->addToAdminLog($member, 'remove_bulkpayer', 'একাধিক পরিশোধকারী অব্যহতি', []);

        Session::flash('success', 'সফলভাবে একাধিক পরিশোধকারী থেকে অব্যহতি দেওয়া হয়েছে!');
        return redirect()->route('dashboard.bulkpayers');
    }

    public function getDonors()
    {
        $donors = Donor::orderBy('id', 'desc')->paginate(10);
        $donations = Donation::orderBy('id', 'desc')->paginate(10);
        $totaldonation = DB::table('donations')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->first();

        return view('dashboard.adminsandothers.donors')
            ->withDonors($donors)
            ->withDonations($donations)
            ->withTotaldonation($totaldonation);
    }

    public function storeDonor(Request $request)
    {
        $this->validate($request, array(
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'email' => 'required|max:255',
            'phone' => 'required|max:255'
        ));

        $donor = new Donor;
        $donor->name = $request->name;
        $donor->address = $request->address;
        $donor->email = $request->email;
        $donor->phone = $request->phone;
        $donor->save();

        Session::flash('success', 'সফলভাবে Donor (দাতা) সংরক্ষন হয়েছে!');
        return redirect()->route('dashboard.donors');
    }

    public function storeDonation(Request $request)
    {
        $this->validate($request, array(
            'donor_id' => 'required',
            'submitter_id' => 'required',
            'amount' => 'required|integer',
            'bank' => 'required',
            'branch' => 'required',
            'pay_slip' => 'required',
            'image' => 'sometimes|image|max:500'
        ));

        $donation = new Donation;
        $donation->donor_id = $request->donor_id;
        $donation->submitter_id = $request->submitter_id;
        $donation->amount = $request->amount;
        $donation->bank = $request->bank;
        $donation->branch = $request->branch;
        $donation->pay_slip = $request->pay_slip;
        $donation->payment_status = 0;
        // generate payment_key
        $payment_key_length = 10;
        $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $payment_key = substr(str_shuffle(str_repeat($pool, 10)), 0, $payment_key_length);
        // generate payment_key
        $donation->payment_key = $payment_key;
        // receipt upload
        if ($request->hasFile('image')) {
            $receipt = $request->file('image');
            $filename = $request->submitter_id . '_donation_receipt_' . time() . '.' . $receipt->getClientOriginalExtension();
            $location = public_path('/images/receipts/' . $filename);
            Image::make($receipt)->resize(800, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($location);
            $donation->image = $filename;
        }
        $donation->save();

        Session::flash('success', 'ডোনেশন সফলভাবে দাখিল করা হয়েছে!');
        return redirect()->route('dashboard.donors');
    }

    public function approveDonation(Request $request, $id)
    {
        $donation = Donation::find($id);
        $donation->payment_status = 1;
        $donation->save();

        Session::flash('success', 'অনুমোদন সফল হয়েছে!');
        return redirect()->route('dashboard.donors');
    }

    public function getDonationofDonor($id)
    {
        $donor = Donor::find($id);
        $donations = Donation::where('donor_id', $id)->paginate(10);
        $totalapproved = DB::table('donations')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->where('donor_id', $id)
            ->first();

        return view('dashboard.adminsandothers.donationsofdonor')
            ->withDonor($donor)
            ->withDonations($donations)
            ->withTotalapproved($totalapproved);
    }

    public function updateDonor(Request $request, $id)
    {
        $this->validate($request, array(
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'email' => 'required|max:255',
            'phone' => 'required|max:255'
        ));

        $donor = Donor::find($id);
        $donor->name = $request->name;
        $donor->address = $request->address;
        $donor->email = $request->email;
        $donor->phone = $request->phone;
        $donor->save();

        Session::flash('success', 'সফলভাবে Donor (দাতা) হালনাগাদ হয়েছে!');
        return redirect()->route('dashboard.donors');
    }

    public function getBranchPayments()
    {
        $branches = Branch::orderBy('id', 'desc')->paginate(10);
        $branchpayments = Branchpayment::orderBy('id', 'desc')->paginate(10);
        $totalbranchpayment = DB::table('branchpayments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->first();

        return view('dashboard.adminsandothers.branchepayments')
            ->withBranches($branches)
            ->withBranchpayments($branchpayments)
            ->withTotalbranchpayment($totalbranchpayment);
    }

    public function getBranches()
    {
        $branches = Branch::orderBy('id', 'asc')->where('id', '>', 0)->paginate(15);

        return view('dashboard.adminsandothers.branches')
            ->withBranches($branches);
    }

    public function getBranchMembers(Request $request, $branch_id)
    {
        $branch = Branch::find($branch_id);
        $memberscount = User::where('activation_status', 1)
            ->where('branch_id', $branch_id)
            ->where('role_type', '!=', 'admin')
            ->count();
        $members = User::where('activation_status', 1)
            ->where('branch_id', $branch_id)
            ->where('role_type', '!=', 'admin')
            ->orderBy('id', 'desc')->get();

        $ordered_member_array = [];
        foreach ($members as $member) {
            $ordered_member_array[(int)substr($member->member_id, -5)] = $member;
        }
        ksort($ordered_member_array); // ascending order according to key

        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $itemCollection = collect($ordered_member_array);

        $perPage = 20;

        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems = new LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        return view('dashboard.membership.branchmembers')
            ->withMembers($paginatedItems)
            ->withMemberscount($memberscount)
            ->withBranch($branch);
    }

    public function storeBranch(Request $request)
    {
        $this->validate($request, array(
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'phone' => 'required|max:255'
        ));

        $branch = new Branch;
        $branch->name = $request->name;
        $branch->address = $request->address;
        $branch->phone = $request->phone;
        $branch->save();
        $this->addToAdminLog($branch, 'add_branch', 'ব্রাঞ্চ সংরক্ষন', []);

        Session::flash('success', 'সফলভাবে ব্রাঞ্চ সংরক্ষন হয়েছে!');
        return redirect()->route('dashboard.branches');
    }

    public function updateBranch(Request $request, $id)
    {
        $this->validate($request, array(
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'phone' => 'required|max:255'
        ));

        $branch = Branch::find($id);
        $branch->name = $request->name;
        $branch->address = $request->address;
        $branch->phone = $request->phone;
        $branch->save();
        $this->addToAdminLog($branch, 'update_branch', 'ব্রাঞ্চ হালনাগাদ', []);

        Session::flash('success', 'সফলভাবে ব্রাঞ্চ হালনাগাদ হয়েছে!');
        return redirect()->route('dashboard.branches');
    }

    public function storeBranchPayment(Request $request)
    {
        $this->validate($request, array(
            'branch_id' => 'required',
            'submitter_id' => 'required',
            'amount' => 'required|integer',
            'bank' => 'required',
            'branch_name' => 'required',
            'pay_slip' => 'required',
            'image' => 'sometimes|image|max:500'
        ));

        $branchpayment = new Branchpayment;
        $branchpayment->branch_id = $request->branch_id;
        $branchpayment->submitter_id = $request->submitter_id;
        $branchpayment->amount = $request->amount;
        $branchpayment->bank = $request->bank;
        $branchpayment->branch_name = $request->branch_name;
        $branchpayment->pay_slip = $request->pay_slip;
        $branchpayment->payment_status = 0;
        // generate payment_key
        $payment_key_length = 10;
        $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $payment_key = substr(str_shuffle(str_repeat($pool, 10)), 0, $payment_key_length);
        // generate payment_key
        $branchpayment->payment_key = $payment_key;
        // receipt upload
        if ($request->hasFile('image')) {
            $receipt = $request->file('image');
            $filename = $request->submitter_id . '_branch_payment_receipt_' . time() . '.' . $receipt->getClientOriginalExtension();
            $location = public_path('/images/receipts/' . $filename);
            Image::make($receipt)->resize(800, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($location);
            $branchpayment->image = $filename;
        }
        $branchpayment->save();
        $this->addToAdminLog($branchpayment, 'add_branchpayment', 'ব্রাঞ্চ পরিশোধ সংরক্ষণ', []);


        Session::flash('success', 'সফলভাবে ব্রাঞ্চ পরিশোধ সংরক্ষণ');
        return redirect()->route('dashboard.branches.payments');
    }

    public function approveBranchPayment(Request $request, $id)
    {
        $branchpayment = Branchpayment::find($id);
        $branchpayment->payment_status = 1;
        $branchpayment->save();
        $this->addToAdminLog($branchpayment, 'approve_branchpayment', 'ব্রাঞ্চ পরিশোধ অনুমোদন', []);

        Session::flash('success', 'অনুমোদন সফল হয়েছে!');
        return redirect()->route('dashboard.branches.payments');
    }

    public function getPaymentofBranch($id)
    {
        $branch = branch::find($id);
        $branchpayments = Branchpayment::where('branch_id', $id)->paginate(10);
        $totalapproved = DB::table('branchpayments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->where('branch_id', $id)
            ->first();

        return view('dashboard.adminsandothers.paymentofbranch')
            ->withBranch($branch)
            ->withBranchpayments($branchpayments)
            ->withTotalapproved($totalapproved);
    }

    public function getDesignations()
    {
        $positions = Position::orderBy('id', 'asc')
            ->where('id', '>', 0)
            ->where('id', '!=', 34)
            ->paginate(15);

        $memberpos = Position::where('id', 34)->first(); // for the 34th, সদস্য!

        return view('dashboard.adminsandothers.positions')
            ->withPositions($positions)
            ->withMemberpos($memberpos);
    }

    public function getDesignationMembers(Request $request, $position_id)
    {
        $designation = Position::find($position_id);
        $memberscount = User::where('activation_status', 1)
            ->where('position_id', $position_id)
            ->where('role_type', '!=', 'admin')
            ->count();
        $members = User::where('activation_status', 1)
            ->where('position_id', $position_id)
            ->where('role_type', '!=', 'admin')
            ->orderBy('id', 'desc')->get();

        $ordered_member_array = [];
        foreach ($members as $member) {
            $ordered_member_array[(int)substr($member->member_id, -5)] = $member;
        }
        ksort($ordered_member_array); // ascending order according to key

        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $itemCollection = collect($ordered_member_array);

        $perPage = 20;

        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems = new LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        return view('dashboard.membership.designationmembers')
            ->withMembers($paginatedItems)
            ->withMemberscount($memberscount)
            ->withDesignation($designation);
    }

    public function getAbouts()
    {
        $about = About::where('type', 'about')->get()->first();
        $whoweare = About::where('type', 'whoweare')->get()->first();
        $whatwedo = About::where('type', 'whatwedo')->get()->first();
        $ataglance = About::where('type', 'ataglance')->get()->first();
        $membership = About::where('type', 'membership')->get()->first();
        $mission = About::where('type', 'mission')->get()->first();
        $basicinfo = Basicinfo::where('id', 1)->first();

        return view('dashboard.abouts')
            ->withAbout($about)
            ->withWhoweare($whoweare)
            ->withWhatwedo($whatwedo)
            ->withAtaglance($ataglance)
            ->withMembership($membership)
            ->withMission($mission)
            ->withBasicinfo($basicinfo);
    }

    public function updateAbouts(Request $request, $id)
    {
        $this->validate($request, array(
            'text' => 'required',
        ));

        $about = About::find($id);
        $about->text = nl2br($request->text);

        $about->save();

        Session::flash('success', 'Updated Successfully!');
        return redirect()->route('dashboard.abouts');
    }

    public function updateBasicInfo(Request $request, $id)
    {
        $this->validate($request, array(
            'address' => 'required',
            'contactno' => 'required',
            'email' => 'required',
            'fb' => 'sometimes',
            'twitter' => 'sometimes',
            'gplus' => 'sometimes',
            'ytube' => 'sometimes',
            'linkedin' => 'sometimes'
        ));

        $basicinfo = Basicinfo::find($id);
        $basicinfo->address = $request->address;
        $basicinfo->contactno = $request->contactno;
        $basicinfo->email = $request->email;
        $basicinfo->fb = $request->fb;
        $basicinfo->twitter = $request->twitter;
        $basicinfo->gplus = $request->gplus;
        $basicinfo->ytube = $request->ytube;
        $basicinfo->linkedin = $request->linkedin;

        $basicinfo->save();

        Session::flash('success', 'Updated Successfully!');
        return redirect()->route('dashboard.abouts');
    }

    public function getCommittee()
    {
        $committeemembers = Committee::orderBy('committee_type', 'desc')
            ->orderBy('serial', 'asc')->get();
        return view('dashboard.committee')->withCommitteemembers($committeemembers);
    }

    public function storeCommittee(Request $request)
    {
        $this->validate($request, array(
            'name' => 'required|max:255',
            'email' => 'required|email',
            'phone' => 'required|numeric',
            'designation' => 'required|max:255',
            'fb' => 'sometimes|max:255',
            'twitter' => 'sometimes|max:255',
            'linkedin' => 'sometimes|max:255',
            'image' => 'sometimes|image|max:500',
            'committee_type' => 'required',
            'serial' => 'required'
        ));

        $committeemember = new Committee();
        $committeemember->committee_type = $request->committee_type;
        $committeemember->name = $request->name;
        $committeemember->email = $request->email;
        $committeemember->phone = $request->phone;
        $committeemember->designation = $request->designation;
        $committeemember->fb = htmlspecialchars(preg_replace("/\s+/", " ", $request->fb));
        $committeemember->twitter = htmlspecialchars(preg_replace("/\s+/", " ", $request->twitter));
        $committeemember->linkedin = htmlspecialchars(preg_replace("/\s+/", " ", $request->linkedin));
        $committeemember->serial = $request->serial;

        // image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = 'member_' . time() . '.' . $image->getClientOriginalExtension();
            $location = public_path('/images/committee/' . $filename);
            Image::make($image)->resize(250, 250)->save($location);
            $committeemember->image = $filename;
        }

        $committeemember->save();

        Session::flash('success', 'সফলভাবে সংরক্ষণ করা হয়েছে!');
        return redirect()->route('dashboard.committee');
    }

    public function updateCommittee(Request $request, $id)
    {
        $this->validate($request, array(
            'name' => 'required|max:255',
            'email' => 'required|email',
            'phone' => 'required|numeric',
            'designation' => 'required|max:255',
            'fb' => 'sometimes|max:255',
            'twitter' => 'sometimes|max:255',
            'linkedin' => 'sometimes|max:255',
            'image' => 'sometimes|image|max:250',
            'committee_type' => 'required',
            'serial' => 'required'
        ));

        $committeemember = Committee::find($id);
        $committeemember->committee_type = $request->committee_type;
        $committeemember->name = $request->name;
        $committeemember->email = $request->email;
        $committeemember->phone = $request->phone;
        $committeemember->designation = $request->designation;
        $committeemember->fb = htmlspecialchars(preg_replace("/\s+/", " ", $request->fb));
        $committeemember->twitter = htmlspecialchars(preg_replace("/\s+/", " ", $request->twitter));
        $committeemember->linkedin = htmlspecialchars(preg_replace("/\s+/", " ", $request->linkedin));
        $committeemember->serial = $request->serial;

        // image upload
        if ($request->hasFile('image')) {
            $image_path = public_path('/images/committee/' . $committeemember->image);
            if (File::exists($image_path)) {
                File::delete($image_path);
            }
            $image = $request->file('image');
            $filename = 'member_' . time() . '.' . $image->getClientOriginalExtension();
            $location = public_path('/images/committee/' . $filename);
            Image::make($image)->resize(250, 250)->save($location);
            $committeemember->image = $filename;
        }

        $committeemember->save();

        Session::flash('success', 'সফলভাবে হালনাগাদ করা হয়েছে!');
        return redirect()->route('dashboard.committee');
    }

    public function deleteCommittee($id)
    {
        $committeemember = Committee::find($id);
        $image_path = public_path('images/committee/' . $committeemember->image);
        if (File::exists($image_path)) {
            File::delete($image_path);
        }
        $committeemember->delete();

        Session::flash('success', 'সফলভাবে মুছে দেওয়া হয়েছে!');
        return redirect()->route('dashboard.committee');
    }

    public function getNews()
    {
        return view('dashboard.index');
    }

    public function getNotice()
    {
        $notices = Notice::orderBy('id', 'desc')->paginate(10);
        return view('dashboard.notice')->withNotices($notices);
    }

    public function storeNotice(Request $request)
    {
        $this->validate($request, array(
            'name' => 'required',
            'attachment' => 'required|mimes:doc,docx,ppt,pptx,png,jpeg,jpg,pdf,gif,txt|max:10000'
        ));

        $notice = new Notice;
        $notice->name = $request->name;

        if ($request->hasFile('attachment')) {
            $newfile = $request->file('attachment');
            $filename = 'file_' . time() . '.' . $newfile->getClientOriginalExtension();
            $location = public_path('/files/');
            $newfile->move($location, $filename);
            $notice->attachment = $filename;
        }

        $notice->save();

        Session::flash('success', 'Notice has been created successfully!');
        return redirect()->route('dashboard.notice');
    }

    public function updateNotice(Request $request, $id)
    {
        $this->validate($request, array(
            'name' => 'required',
            'attachment' => 'sometimes|mimes:doc,docx,ppt,pptx,png,jpeg,jpg,pdf,gif,txt|max:10000'
        ));

        $notice = Notice::find($id);
        $notice->name = $request->name;

        if ($request->hasFile('attachment')) {
            // delete the previous one
            $file_path = public_path('files/' . $notice->attachment);
            if (File::exists($file_path)) {
                File::delete($file_path);
            }
            $newfile = $request->file('attachment');
            $filename = 'file_' . time() . '.' . $newfile->getClientOriginalExtension();
            $location = public_path('/files/');
            $newfile->move($location, $filename);
            $notice->attachment = $filename;
        }

        $notice->save();

        Session::flash('success', 'Notice has been updated successfully!');
        return redirect()->route('dashboard.notice');
    }

    public function deleteNotice($id)
    {
        $notice = Notice::find($id);
        $file_path = public_path('files/' . $notice->attachment);
        if (File::exists($file_path)) {
            File::delete($file_path);
        }
        $notice->delete();

        Session::flash('success', 'Deleted Successfully!');
        return redirect()->route('dashboard.notice');
    }

    public function getFAQ()
    {
        $faqs = Faq::orderBy('id', 'desc')->paginate(10);
        return view('dashboard.faq')->withFaqs($faqs);
    }

    public function storeFAQ(Request $request)
    {
        $this->validate($request, array(
            'question' => 'required|max:255',
            'answer' => 'required'
        ));

        $faq = new Faq;
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->save();

        Session::flash('success', 'প্রশ্ন-উত্তর সফলভাবে সংরক্ষন করা হয়েছে!');
        return redirect()->route('dashboard.faq');
    }

    public function updateFAQ(Request $request, $id)
    {
        $this->validate($request, array(
            'question' => 'required|max:255',
            'answer' => 'required'
        ));

        $faq = Faq::find($id);
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->save();

        Session::flash('success', 'প্রশ্ন-উত্তর সফলভাবে হালনাগাদ করা হয়েছে!');
        return redirect()->route('dashboard.faq');
    }

    public function deleteFAQ($id)
    {
        $faq = Faq::find($id);
        $faq->delete();

        Session::flash('success', 'প্রশ্ন-উত্তর সফলভাবে মুছে দেওয়া হয়েছে!');
        return redirect()->route('dashboard.faq');
    }

    public function getEvents()
    {
        $events = Event::orderBy('id', 'desc')->paginate(10);
        return view('dashboard.event')->withEvents($events);
    }

    public function storeEvent(Request $request)
    {
        $this->validate($request, array(
            'name' => 'required',
            'description' => 'required',
            'image' => 'sometimes|image|max:500'
        ));

        $event = new Event;
        $event->name = $request->name;
        $event->description = $request->description;

        // image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = 'event_' . time() . '.' . $image->getClientOriginalExtension();
            $location = public_path('/images/events/' . $filename);
            Image::make($image)->resize(400, 250)->save($location);
            $event->image = $filename;
        }

        $event->save();

        Session::flash('success', 'Event has been created successfully!');
        return redirect()->route('dashboard.events');
    }

    public function updateEvent(Request $request, $id)
    {
        $this->validate($request, array(
            'name' => 'required',
            'description' => 'required',
            'image' => 'sometimes|image|max:500'
        ));

        $event = Event::find($id);
        $event->name = $request->name;
        $event->description = $request->description;

        // image upload
        if ($request->hasFile('image')) {
            // delete the previous one
            $image_path = public_path('images/events/' . $event->image);
            if (File::exists($image_path)) {
                File::delete($image_path);
            }
            $image = $request->file('image');
            $filename = 'event_' . time() . '.' . $image->getClientOriginalExtension();
            $location = public_path('/images/events/' . $filename);
            Image::make($image)->resize(400, 250)->save($location);
            $event->image = $filename;
        }

        $event->save();

        Session::flash('success', 'Event has been updated successfully!');
        return redirect()->route('dashboard.events');
    }

    public function deleteEvent($id)
    {
        $event = Event::find($id);
        $image_path = public_path('images/events/' . $event->image);
        if (File::exists($image_path)) {
            File::delete($image_path);
        }
        $event->delete();

        Session::flash('success', 'Deleted Successfully!');
        return redirect()->route('dashboard.events');
    }

    public function getSlider()
    {
        $sliders = Slider::orderBy('id', 'desc')->paginate(10);
        return view('dashboard.slider')->withSliders($sliders);
    }

    public function storeSlider(Request $request)
    {
        $this->validate($request, array(
            'title' => 'required',
            'image' => 'sometimes|image|max:1000'
        ));

        $slider = new Slider;
        $slider->title = $request->title;

        // slider upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = 'slider_' . time() . '.' . $image->getClientOriginalExtension();
            $location = public_path('/images/slider/' . $filename);
            Image::make($image)->resize(1500, 500)->save($location);
            $slider->image = $filename;
        }

        $slider->save();

        Session::flash('success', 'সফলভাবে স্লাইডারের ছবি আপলোড করা হয়েছে!');
        return redirect()->route('dashboard.slider');
    }

    public function deleteSlider($id)
    {
        $slider = Slider::find($id);
        $image_path = public_path('images/slider/' . $slider->image);
        if (File::exists($image_path)) {
            File::delete($image_path);
        }
        $slider->delete();

        Session::flash('success', 'সফলভাবে মুছে ফেলা হয়েছে!');
        return redirect()->route('dashboard.slider');
    }

    public function getGallery()
    {
        $albums = Album::orderBy('id', 'desc')->paginate(10);
        return view('dashboard.gallery.index')->withAlbums($albums);
    }

    public function getCreateGallery()
    {
        return view('dashboard.gallery.create');
    }

    public function storeGalleryAlbum(Request $request)
    {
        $this->validate($request, array(
            'name' => 'required',
            'description' => 'sometimes',
            'thumbnail' => 'required|image|max:500',
            'image1' => 'sometimes|image|max:500',
            'image2' => 'sometimes|image|max:500',
            'image3' => 'sometimes|image|max:500',
            'caption1' => 'sometimes',
            'caption2' => 'sometimes',
            'caption3' => 'sometimes'

        ));

        $album = new Album;
        $album->name = $request->name;
        $album->description = $request->description;

        // thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $filename = 'thumbnail_' . time() . '.' . $thumbnail->getClientOriginalExtension();
            $location = public_path('/images/gallery/' . $filename);
            Image::make($thumbnail)->resize(1000, 625)->save($location);
            $album->thumbnail = $filename;
        }

        $album->save();

        // photo (s) upload
        for ($i = 1; $i <= 3; $i++) {
            if ($request->hasFile('image' . $i)) {
                $image = $request->file('image' . $i);
                $filename = 'photo_' . $i . time() . '.' . $image->getClientOriginalExtension();
                $location = public_path('/images/gallery/' . $filename);
                Image::make($image)->resize(1000, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($location);
                $albumphoto = new Albumphoto;
                $albumphoto->album_id = $album->id;
                $albumphoto->image = $filename;
                $albumphoto->caption = $request->input('caption' . $i);
                $albumphoto->save();
            }
        }

        Session::flash('success', 'Album has been created successfully!');
        return redirect()->route('dashboard.gallery');
    }

    public function getEditGalleryAlbum($id)
    {
        $album = Album::find($id);
        return view('dashboard.gallery.edit')->withAlbum($album);
    }

    public function updateGalleryAlbum(Request $request, $id)
    {
        $this->validate($request, array(
            'name' => 'required',
            'description' => 'required',
            'image' => 'sometimes|image|max:500',
            'caption' => 'sometimes'
        ));

        $album = Album::find($id);
        $album->name = $request->name;
        $album->description = $request->description;
        $album->save();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = 'photo_' . time() . '.' . $image->getClientOriginalExtension();
            $location = public_path('/images/gallery/' . $filename);
            Image::make($image)->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($location);
            $albumphoto = new Albumphoto;
            $albumphoto->album_id = $album->id;
            $albumphoto->image = $filename;
            $albumphoto->caption = $request->caption;
            $albumphoto->save();
        }

        Session::flash('success', 'Uploaded successfully!');
        return redirect()->route('dashboard.editgallery', $id);
    }

    public function deleteAlbum($id)
    {
        $album = Album::find($id);
        $thumbnail_path = public_path('images/gallery/' . $album->thumbnail);
        if (File::exists($thumbnail_path)) {
            File::delete($thumbnail_path);
        }
        if ($album->albumphotoes->count() > 0) {
            foreach ($album->albumphotoes as $albumphoto) {
                $image_path = public_path('images/gallery/' . $albumphoto->image);
                if (File::exists($image_path)) {
                    File::delete($image_path);
                }
            }
        }
        $album->delete();

        Session::flash('success', 'Deleted Successfully!');
        return redirect()->route('dashboard.gallery');
    }

    public function deleteSinglePhoto($id)
    {
        $albumphoto = Albumphoto::find($id);
        $image_path = public_path('images/gallery/' . $albumphoto->image);
        if (File::exists($image_path)) {
            File::delete($image_path);
        }
        $albumphoto->delete();

        Session::flash('success', 'Deleted Successfully!');
        return redirect()->route('dashboard.editgallery', $albumphoto->album->id);
    }

    public function getBlogs()
    {
        return view('dashboard.index');
    }

    public function getApplications()
    {
        $applications = User::where('activation_status', 0)
            ->orderBy('id', 'asc')->paginate(20);
        $applicationscount = User::where('activation_status', 0)
            ->where('role_type', '!=', 'admin')->count();

        return view('dashboard.membership.applications')
            ->withApplications($applications)
            ->withapplicationscount($applicationscount);
    }

    public function getSignleApplication($unique_key)
    {
        $application = User::where('unique_key', $unique_key)->first();

        return view('dashboard.membership.singleapplication')
            ->withApplication($application);
    }

    public function getSignleApplicationEdit($unique_key)
    {
        $positions = Position::where('id', '>', 0)->get();
        $branches = Branch::where('id', '>', 0)->get();
        $application = User::where('unique_key', $unique_key)->first(); // this is also used to edit MEMBERS!
        $upazillas = Upazilla::groupBy('district_bangla')->get();

        return view('dashboard.membership.singleapplicationedit')
            ->withApplication($application)
            ->withBranches($branches)
            ->withPositions($positions)
            ->withUpazillas($upazillas);
    }

    public function updateSignleApplication(Request $request, $id)
    {
        $application = User::find($id);

        if ($application->activation_status == 0) {
            $this->validate($request, array(
                'name_bangla' => 'required|max:255',
                'name' => 'required|max:255',
                'nid' => 'required|max:255',
                'dob' => 'required|max:255',
                'gender' => 'required',
                'spouse' => 'sometimes|max:255',
                'spouse_profession' => 'sometimes|max:255',
                'father' => 'required|max:255',
                'mother' => 'required|max:255',
                'profession' => 'required|max:255',
                'position_id' => 'required',
                'branch_id' => 'required',
                'joining_date' => 'sometimes|max:255',
                'present_address' => 'required|max:255',
                'permanent_address' => 'required|max:255',
                'office_telephone' => 'sometimes|max:255',
                'mobile' => 'required|max:11',
                'home_telephone' => 'sometimes|max:255',
                'email' => 'sometimes|email',
                'image' => 'sometimes|image|max:250', // jehetu up korse ekbar, ekhane na korleo cholbe

                'nominee_one_name' => 'required|max:255',
                'nominee_one_identity_type' => 'required',
                'nominee_one_identity_text' => 'required|max:255',
                'nominee_one_relation' => 'required|max:255',
                'nominee_one_percentage' => 'required|max:255',
                'nominee_one_image' => 'sometimes|image|max:250', // jehetu up korse ekbar, ekhane na korleo cholbe

                'nominee_two_name' => 'sometimes|max:255',
                'nominee_two_identity_type' => 'sometimes',
                'nominee_two_identity_text' => 'sometimes|max:255',
                'nominee_two_relation' => 'sometimes|max:255',
                'nominee_two_percentage' => 'sometimes|max:255',
                'nominee_two_image' => 'sometimes|image|max:250',

                'application_payment_amount' => 'required|max:255',
                'application_payment_bank' => 'required|max:255',
                'application_payment_branch' => 'required|max:255',
                'application_payment_pay_slip' => 'required|max:255',
                'application_payment_receipt' => 'sometimes|image|max:2048', // jehetu up korse ekbar, ekhane na korleo cholbe
            ));
        } else {
            $this->validate($request, array(
                'name_bangla' => 'required|max:255',
                'name' => 'required|max:255',
                'nid' => 'required|max:255',
                'dob' => 'required|max:255',
                'gender' => 'required',
                'spouse' => 'sometimes|max:255',
                'spouse_profession' => 'sometimes|max:255',
                'father' => 'required|max:255',
                'mother' => 'required|max:255',
                'profession' => 'required|max:255',
                'position_id' => 'required|max:255',
                'branch_id' => 'required',
                'joining_date' => 'sometimes|max:255',
                'present_address' => 'required|max:255',
                'permanent_address' => 'required|max:255',
                'office_telephone' => 'sometimes|max:255',
                'mobile' => 'required|max:11',
                'home_telephone' => 'sometimes|max:255',
                'email' => 'sometimes|email',
                'image' => 'sometimes|image|max:250', // jehetu up korse ekbar, ekhane na korleo cholbe

                'nominee_one_name' => 'required|max:255',
                'nominee_one_identity_type' => 'required',
                'nominee_one_identity_text' => 'required|max:255',
                'nominee_one_relation' => 'required|max:255',
                'nominee_one_percentage' => 'required|max:255',
                'nominee_one_image' => 'sometimes|image|max:250', // jehetu up korse ekbar, ekhane na korleo cholbe

                'nominee_two_name' => 'sometimes|max:255',
                'nominee_two_identity_type' => 'sometimes',
                'nominee_two_identity_text' => 'sometimes|max:255',
                'nominee_two_relation' => 'sometimes|max:255',
                'nominee_two_percentage' => 'sometimes|max:255',
                'nominee_two_image' => 'sometimes|image|max:250',

                'start_date' => 'sometimes',
                'blood_group' => 'sometimes',
                'upazilla_id' => 'sometimes| numeric',
                'prl_date' => 'sometimes',
                'application_hard_copy' => 'sometimes|image|max:4096',
                'digital_signature' => 'sometimes|image|max:250',

            ));
        }


        if ($request->mobile != $application->mobile) {
            $findmobileuser = User::where('mobile', $request->mobile)->first();

            if ($findmobileuser) {
                Session::flash('warning', 'দুঃখিত! মোবাইল নম্বরটি ব্যবহৃত হয়ে গেছে; আরেকটি দিন');
                return redirect()->route('dashboard.singleapplicationedit', $application->unique_key);
            }
        }
        if ($request->email != $application->email) {
            $findemailuser = User::where('email', $request->email)->first();

            if ($findemailuser) {
                Session::flash('warning', 'দুঃখিত! ইমেইলটি ব্যবহৃত হয়ে গেছে; আরেকটি দিন');
                return redirect()->route('dashboard.singleapplicationedit', $application->unique_key);
            }
        }

        //career log entry for activated users
        if ($application->activation_status == 1 && ($application->position_id != $request->position_id || $application->branch_id != $request->branch_id)) {

            if (!$request->has('start_date') || DateTime::createFromFormat('d-m-Y', $request->start_date) == false) {
                Session::flash('warning', 'আপনি নতুন পদবি/দপ্তর এ যোগদানের তারিখ দেননি!');
                return redirect()->route('dashboard.singleapplicationedit', $application->unique_key);
            }
            $newCareerLog = new Careerlog();
            $newCareerLog->user_id = $application->id;
            $newCareerLog->branch_id = $request->branch_id;
            $newCareerLog->position_id = $request->position_id;
            $newCareerLog->start_date = Carbon::parse($request->start_date);
            $newCareerLog->prev_branch_name = ($application->branch_id != 0) ? $application->branch->name : $application->office;
            $newCareerLog->prev_position_name = ($application->position_id != 0) ? $application->position->name : $application->designation;
            $newCareerLog->save();
        }


        $application->name_bangla = htmlspecialchars(preg_replace("/\s+/", " ", $request->name_bangla));
        $application->name = htmlspecialchars(preg_replace("/\s+/", " ", ucwords($request->name)));
        $application->nid = htmlspecialchars(preg_replace("/\s+/", " ", $request->nid));
        $dob = htmlspecialchars(preg_replace("/\s+/", " ", $request->dob));
        $application->dob = new Carbon($dob);
        $application->gender = htmlspecialchars(preg_replace("/\s+/", " ", $request->gender));
        $application->spouse = htmlspecialchars(preg_replace("/\s+/", " ", $request->spouse));
        $application->spouse_profession = htmlspecialchars(preg_replace("/\s+/", " ", $request->spouse_profession));
        $application->father = htmlspecialchars(preg_replace("/\s+/", " ", $request->father));
        $application->mother = htmlspecialchars(preg_replace("/\s+/", " ", $request->mother));
        // $application->office = htmlspecialchars(preg_replace("/\s+/", " ", $request->office));
        $application->branch_id = $request->branch_id;
        if ($request->joining_date != '') {
            $joining_date = htmlspecialchars(preg_replace("/\s+/", " ", $request->joining_date));
            $application->joining_date = new Carbon($joining_date);
        }
        $application->profession = htmlspecialchars(preg_replace("/\s+/", " ", $request->profession));
        // $application->designation = htmlspecialchars(preg_replace("/\s+/", " ", $request->designation));
        $application->position_id = $request->position_id;
        $application->membership_designation = htmlspecialchars(preg_replace("/\s+/", " ", $request->designation));
        $application->present_address = htmlspecialchars(preg_replace("/\s+/", " ", $request->present_address));
        $application->permanent_address = htmlspecialchars(preg_replace("/\s+/", " ", $request->permanent_address));
        $application->office_telephone = htmlspecialchars(preg_replace("/\s+/", " ", $request->office_telephone));
        $application->mobile = htmlspecialchars(preg_replace("/\s+/", " ", $request->mobile));
        $application->home_telephone = htmlspecialchars(preg_replace("/\s+/", " ", $request->home_telephone));
        if ($request->email != '') {
            $application->email = htmlspecialchars(preg_replace("/\s+/", " ", $request->email));
        } else {
            $application->email = htmlspecialchars(preg_replace("/\s+/", " ", $request->mobile)) . '@cvcsbd.com';
        }

        // applicant's image upload
        if ($request->hasFile('image')) {
            $old_img = public_path('images/users/' . $application->image);
            if (File::exists($old_img)) {
                File::delete($old_img);
            }
            $image = $request->file('image');
            $filename = str_replace(' ', '', $request->name) . time() . '.' . $image->getClientOriginalExtension();
            $location = public_path('/images/users/' . $filename);
            Image::make($image)->resize(200, 200)->save($location);
            $application->image = $filename;
        }

        if ($request->hasFile('application_hard_copy')) {
            $old_img = public_path('images/users/' . $application->application_hard_copy);
            if (File::exists($old_img)) {
                File::delete($old_img);
            }
            $image = $request->file('application_hard_copy');
            $filename = 'application_hard_copy_' . str_replace(' ', '', $request->name) . time() . '.' . $image->getClientOriginalExtension();
            $location = public_path('/images/users/' . $filename);
            Image::make($image)->save($location);
            $application->application_hard_copy = $filename;
        }

        if ($request->hasFile('digital_signature')) {
            $old_img = public_path('images/users/' . $application->digital_signature);
            if (File::exists($old_img)) {
                File::delete($old_img);
            }
            $image = $request->file('digital_signature');
            $filename = 'digital_signature_' . str_replace(' ', '',  $request->name) . time() . '.' . $image->getClientOriginalExtension();
            $location = public_path('/images/users/' . $filename);
            Image::make($image)->resize(200, 200)->save($location);
            $application->digital_signature = $filename;
        }


        $application->nominee_one_name = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_name));
        $application->nominee_one_identity_type = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_identity_type));
        $application->nominee_one_identity_text = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_identity_text));
        $application->nominee_one_relation = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_relation));
        $application->nominee_one_percentage = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_percentage));
        // nominee one's image upload
        if ($request->hasFile('nominee_one_image')) {
            $old_nominee_one_image = public_path('images/users/' . $application->nominee_one_image);
            if (File::exists($old_nominee_one_image)) {
                File::delete($old_nominee_one_image);
            }
            $nominee_one_image = $request->file('nominee_one_image');
            $filename = 'nominee_one_' . str_replace(' ', '', $request->name) . time() . '.' . $nominee_one_image->getClientOriginalExtension();
            $location = public_path('/images/users/' . $filename);
            Image::make($nominee_one_image)->resize(200, 200)->save($location);
            $application->nominee_one_image = $filename;
        }

        $application->nominee_two_name = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_name));
        $application->nominee_two_identity_type = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_identity_type));
        $application->nominee_two_identity_text = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_identity_text));
        $application->nominee_two_relation = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_relation));
        $application->nominee_two_percentage = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_percentage));
        // nominee two's image upload
        if ($request->hasFile('nominee_two_image')) {
            $old_nominee_two_image = public_path('images/users/' . $application->nominee_two_image);
            if (File::exists($old_nominee_two_image)) {
                File::delete($old_nominee_two_image);
            }
            $nominee_two_image = $request->file('nominee_two_image');
            $filename = 'nominee_two_' . str_replace(' ', '', $request->name) . time() . '.' . $nominee_two_image->getClientOriginalExtension();
            $location = public_path('/images/users/' . $filename);
            Image::make($nominee_two_image)->resize(200, 200)->save($location);
            $application->nominee_two_image = $filename;
        }

        if ($application->activation_status == 0) {
            $application->application_payment_amount = htmlspecialchars(preg_replace("/\s+/", " ", $request->application_payment_amount));
            $application->application_payment_bank = htmlspecialchars(preg_replace("/\s+/", " ", $request->application_payment_bank));
            $application->application_payment_branch = htmlspecialchars(preg_replace("/\s+/", " ", $request->application_payment_branch));
            $application->application_payment_pay_slip = htmlspecialchars(preg_replace("/\s+/", " ", $request->application_payment_pay_slip));
            // application payment receipt's image upload
            if ($request->hasFile('application_payment_receipt')) {
                $old_application_payment_receipt = public_path('images/receipts/' . $application->application_payment_receipt);
                if (File::exists($old_application_payment_receipt)) {
                    File::delete($old_application_payment_receipt);
                }
                $application_payment_receipt = $request->file('application_payment_receipt');
                $filename = 'application_payment_receipt_' . str_replace(' ', '', $request->name) . time() . '.' . $application_payment_receipt->getClientOriginalExtension();
                $location = public_path('/images/receipts/' . $filename);
                Image::make($application_payment_receipt)->save($location);
                $application->application_payment_receipt = $filename;
            }
        }
        $this->addToAdminLog($application, 'update_member', 'সদস্য তথ্য দাখিল', []);
        $application->save();

        if ($application->activation_status == 0 || $application->activation_status == 202) {
            Session::flash('success', 'আবেদনটি সফলভাবে হালনাগাদ করা হয়েছে!');
            return redirect()->route('dashboard.singleapplication', $application->unique_key);
        } else {
            Session::flash('success', 'সদস্য তথ্য সফলভাবে হালনাগাদ করা হয়েছে!');
            return redirect()->route('dashboard.singlemember', $application->unique_key);
        }
    }

    public function getDefectiveApplications()
    {
        $applications = User::where('activation_status', 202)
            ->orderBy('id', 'asc')->paginate(20);
        $applicationscount = User::where('activation_status', 202)
            ->where('role_type', '!=', 'admin')->count();

        return view('dashboard.membership.defectiveapplications')
            ->withApplications($applications)
            ->withapplicationscount($applicationscount);
    }

    public function makeDefectiveApplication(Request $request, $id)
    {
        $application = User::find($id);
        $application->activation_status = 202; // 202 for defective applications
        $application->save();
        Session::flash('success', 'সদস্য সফলভাবে অসম্পূর্ণ তালিকায় প্রেরণ করা হয়েছে!');
        return redirect()->route('dashboard.defectiveapplications');
    }

    public function makeDefectiveToPendingApplication(Request $request, $id)
    {
        $application = User::find($id);
        $application->activation_status = 0; // 0 for pending applications
        $application->save();
        Session::flash('success', 'সদস্য সফলভাবে আবেদনের তালিকায় প্রেরণ করা হয়েছে!');
        return redirect()->route('dashboard.applications');
    }

    public function searchDefectiveApplicationAPI(Request $request)
    {
        if ($request->ajax()) {
            $output = '';
            $query = $request->get('query');
            if ($query != '') {
                $data = DB::table('users')
                    ->where('activation_status', 202) // 202 for defective applications
                    ->where('role_type', '!=', 'admin') // avoid the super admin type
                    ->where(function ($newquery) use ($query) {
                        $newquery->where('name', 'like', '%' . $query . '%')
                            ->orWhere('name_bangla', 'like', '%' . $query . '%')
                            ->orWhere('mobile', 'like', '%' . $query . '%')
                            ->orWhere('email', 'like', '%' . $query . '%');
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            }

            $total_row = count($data);
            if ($total_row > 0) {
                foreach ($data as $row) {
                    $output .= '
            <tr>
             <td>
                <a href="' . route('dashboard.singleapplication', $row->unique_key) . '" title="সদস্য তথ্য দেখুন">
                  ' . $row->name_bangla . '<br/> ' . $row->name . '
                </a>
             </td>
             <td>' . $row->mobile . '<br/>' . $row->email . '</td>
             <td>' . $row->office . '<br/>' . $row->profession . ' (' . $row->designation . ')</td>
             <td>৳ ' . $row->application_payment_amount . '<br/>' . $row->application_payment_bank . ' (' . $row->application_payment_branch . ')</td>
            ';
                    if ($row->image != null) {
                        $output .= '<td><img src="' . asset('images/users/' . $row->image) . '" style="height: 50px; width: auto;" /></td>';
                    } else {
                        $output .= '<td><img src="' . asset('images/user.png') . '" style="height: 50px; width: auto;" /></td>';
                    }
                    $output .= '<td><a class="btn btn-sm btn-success" href="' . route('dashboard.singleapplication', $row->unique_key) . '" title="সদস্য তথ্য দেখুন"><i class="fa fa-eye"></i></a> 
                <a class="btn btn-sm btn-primary" href="' . route('dashboard.singleapplicationedit', $row->unique_key) . '" title="সদস্য তথ্য সম্পাদনা করুণ"><i class="fa fa-edit"></i></a>
              </td>
            </tr>';
                }
            } else {
                $output = '
           <tr>
            <td align="center" colspan="6">পাওয়া যায়নি!</td>
           </tr>
           ';
            }
            $data = array(
                'table_data' => $output,
                'total_data' => $total_row . ' টি ফলাফল পাওয়া গেছে'
            );

            echo json_encode($data);
        }
    }

    public function activateMember(Request $request, $id)
    {
        $application = User::find($id);
        $application->activation_status = 1;

        // $lastmember = User::where('activation_status', 1)
        //                   ->orderBy('updated_at', 'desc')
        //                   ->first();
        // $lastfivedigits = (int) substr($lastmember->member_id, -5);

        $members = User::where('activation_status', 1)->get();
        $ordered_member_ids = [];
        foreach ($members as $member) {
            array_push($ordered_member_ids, (int)substr($member->member_id, -5));
        }
        rsort($ordered_member_ids); // descending order to get the last value

        $application->member_id = date('Y', strtotime($application->dob)) . str_pad(($ordered_member_ids[0] + 1), 5, '0', STR_PAD_LEFT);
        // check if the id already exists...
        $ifexists = User::where('member_id', $application->member_id)->first();
        if ($ifexists) {
            Session::flash('warning', 'দুঃখিত! আবার চেষ্টা করুন!');
            return redirect()->route('dashboard.applications');
        } else {
            $application->save();
            $this->addToAdminLog($application, 'activate_member', 'সদস্য অনুমোদন', []);

            $newmembercheck = User::where('activation_status', 1)
                ->where('member_id', $application->member_id)
                ->first();

            if ($newmembercheck) {
                // dd($newmembercheck);
                // save the payment!
                $payment = new Payment;
                $payment->member_id = $newmembercheck->member_id;
                $payment->payer_id = $newmembercheck->member_id;
                $payment->amount = 5000; // hard coded
                $payment->bank = $newmembercheck->application_payment_bank;
                $payment->branch = $newmembercheck->application_payment_branch;
                $payment->pay_slip = $newmembercheck->application_payment_pay_slip;
                $payment->payment_status = 1; // approved
                $payment->payment_category = 0; // membership payment
                $payment->payment_type = 1; // single payment
                $payment->payment_key = random_string(10);
                $payment->save();
                $this->addToAdminLog($newmembercheck, 'approve_single_payment', 'পেমেন্ট অনুমোদন', ['payment_id' => $payment->id]);

                // receipt upload
                if ($newmembercheck->application_payment_receipt != '') {
                    $paymentreceipt = new Paymentreceipt;
                    $paymentreceipt->payment_id = $payment->id;
                    $paymentreceipt->image = $newmembercheck->application_payment_receipt;
                    $paymentreceipt->save();
                }
                if ($newmembercheck->application_payment_amount > 5000) {
                    $payment = new Payment;
                    $payment->member_id = $newmembercheck->member_id;
                    $payment->payer_id = $newmembercheck->member_id;
                    $payment->amount = $newmembercheck->application_payment_amount - 5000; // IMPORTANT
                    $payment->bank = $newmembercheck->application_payment_bank;
                    $payment->branch = $newmembercheck->application_payment_branch;
                    $payment->pay_slip = $newmembercheck->application_payment_pay_slip;
                    $payment->payment_status = 1; // approved (0 means pending)
                    $payment->payment_category = 1; // monthly payment (0 means membership)
                    $payment->payment_type = 1; // single payment (2 means bulk)
                    $payment->payment_key = random_string(10);
                    $payment->save();
                    $this->addToAdminLog($newmembercheck, 'approve_single_payment', 'পেমেন্ট অনুমোদন', ['payment_id' => $payment->id]);

                    // receipt upload
                    if ($newmembercheck->application_payment_receipt != '') {
                        $paymentreceipt = new Paymentreceipt;
                        $paymentreceipt->payment_id = $payment->id;
                        $paymentreceipt->image = $newmembercheck->application_payment_receipt;
                        $paymentreceipt->save();
                    }
                }
                // save the payment!
            } else {
                Session::flash('warning', 'দুঃখিত! আবার চেষ্টা করুন!');
                return redirect()->back();
            }

            // send activation SMS ... aro kichu kaaj baki ache...
            // send sms
            $mobile_number = 0;
            if (strlen($application->mobile) == 11) {
                $mobile_number = $application->mobile;
            } elseif (strlen($application->mobile) > 11) {
                if (strpos($application->mobile, '+') !== false) {
                    $mobile_number = substr($application->mobile, -11);
                }
            }
            $url = config('sms.url');
            $number = $mobile_number;
            $text = 'Dear ' . $application->name . ', your membership application has been approved! Your ID: ' . $application->member_id . ', Email: ' . $application->email . ' and Password: cvcs12345. Customs and VAT Co-operative Society (CVCS). Login & change password: https://cvcsbd.com/login';
            // this sms costs 2 SMS
            // this sms costs 2 SMS

            $data = array(
                'username' => config('sms.username'),
                'password' => config('sms.password'),
                'number' => "$number",
                'message' => "$text",
            );
            // initialize send status
            $ch = curl_init(); // Initialize cURL
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this is important
            $smsresult = curl_exec($ch);
            $p = explode("|", $smsresult);
            $sendstatus = $p[0];
            // send sms
            if ($sendstatus == 1101) {
                Session::flash('info', 'SMS সফলভাবে পাঠানো হয়েছে!');
            } elseif ($sendstatus == 1006) {
                Session::flash('warning', 'অপর্যাপ্ত SMS ব্যালেন্সের কারণে SMS পাঠানো যায়নি!');
            } else {
                Session::flash('warning', 'দুঃখিত! SMS পাঠানো যায়নি!');
            }

            Session::flash('success', 'সদস্য সফলভাবে অনুমোদন করা হয়েছে!');
            return redirect()->route('dashboard.applications');
        }

    }

    public function deleteApplication(Request $request, $id)
    {
        $application = User::find($id);
        $image_path = public_path('images/users/' . $application->image);
        if (File::exists($image_path)) {
            File::delete($image_path);
        }
        $nominee_one_path = public_path('images/users/' . $application->nominee_one_image);
        if (File::exists($nominee_one_path)) {
            File::delete($nominee_one_path);
        }
        $nominee_two_path = public_path('images/users/' . $application->nominee_two_image);
        if (File::exists($nominee_two_path)) {
            File::delete($nominee_two_path);
        }
        $application->delete();
//        $this->addToAdminLog($application, 'delete_application', 'সদস্য আবেদন বাতিল', []);

        return redirect()->route('dashboard.applications');
    }

    public function sendSMSApplicant(Request $request)
    {
        $this->validate($request, array(
            'unique_key' => 'required',
            'message' => 'required'
        ));

        $applicant = User::where('unique_key', $request->unique_key)->first();

        // send pending SMS ... aro kichu kaaj baki ache...
        // send sms
        $mobile_number = 0;
        if (strlen($applicant->mobile) == 11) {
            $mobile_number = $applicant->mobile;
        } elseif (strlen($applicant->mobile) > 11) {
            if (strpos($applicant->mobile, '+') !== false) {
                $mobile_number = substr($applicant->mobile, -11);
            }
        }
        $url = config('sms.url');
        $number = $mobile_number;
        $text = $request->message . ' Customs and VAT Co-operative Society (CVCS).';
        $data = array(
            'username' => config('sms.username'),
            'password' => config('sms.password'),
            'number' => "$number",
            'message' => "$text",
            // 'apicode'=>"1",
            // 'msisdn'=>"$number",
            // 'countrycode'=>"880",
            // 'cli'=>"CVCS",
            // 'messagetype'=>"3",
            // 'messageid'=>"3"
        );
        // initialize send status
        $ch = curl_init(); // Initialize cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this is important
        $smsresult = curl_exec($ch);
        $p = explode("|", $smsresult);
        $sendstatus = $p[0];

        // $sendstatus = substr($smsresult, 0, 3);
        // API Response Code
        // 1000 = Invalid user or Password
        // 1002 = Empty Number
        // 1003 = Invalid message or empty message
        // 1004 = Invalid number
        // 1005 = All Number is Invalid
        // 1006 = insufficient Balance
        // 1009 = Inactive Account
        // 1010 = Max number limit exceeded
        // 1101 = Success
        // send sms
        if ($sendstatus == 1101) {
            Session::flash('success', 'SMS সফলভাবে পাঠানো হয়েছে!');
        } elseif ($sendstatus == 1006) {
            Session::flash('warning', 'অপর্যাপ্ত SMS ব্যালেন্সের কারণে SMS পাঠানো যায়নি!');
        } else {
            Session::flash('warning', 'দুঃখিত! SMS পাঠানো যায়নি!');
            // return json_encode($smsresult);
        }

        return redirect()->back();
    }

    public function searchApplicationAPI(Request $request)
    {
        if ($request->ajax()) {
            $output = '';
            $query = $request->get('query');
            if ($query != '') {
                $data = DB::table('users')
                    ->where('activation_status', 0)
                    ->where('role_type', '!=', 'admin') // avoid the super admin type
                    ->where(function ($newquery) use ($query) {
                        $newquery->where('name', 'like', '%' . $query . '%')
                            ->orWhere('name_bangla', 'like', '%' . $query . '%')
                            ->orWhere('mobile', 'like', '%' . $query . '%')
                            ->orWhere('email', 'like', '%' . $query . '%');
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            }

            $total_row = count($data);
            if ($total_row > 0) {
                foreach ($data as $row) {
                    $output .= '
            <tr>
             <td>
                <a href="' . route('dashboard.singleapplication', $row->unique_key) . '" title="সদস্য তথ্য দেখুন">
                  ' . $row->name_bangla . '<br/> ' . $row->name . '
                </a>
             </td>
             <td>' . $row->mobile . '<br/>' . $row->email . '</td>
             <td>' . $row->office . '<br/>' . $row->profession . ' (' . $row->designation . ')</td>
             <td>৳ ' . $row->application_payment_amount . '<br/>' . $row->application_payment_bank . ' (' . $row->application_payment_branch . ')</td>
            ';
                    if ($row->image != null) {
                        $output .= '<td><img src="' . asset('images/users/' . $row->image) . '" style="height: 50px; width: auto;" /></td>';
                    } else {
                        $output .= '<td><img src="' . asset('images/user.png') . '" style="height: 50px; width: auto;" /></td>';
                    }
                    $output .= '<td><a class="btn btn-sm btn-success" href="' . route('dashboard.singleapplication', $row->unique_key) . '" title="সদস্য তথ্য দেখুন"><i class="fa fa-eye"></i></a>
                <a class="btn btn-sm btn-primary" href="' . route('dashboard.singleapplicationedit', $row->unique_key) . '" title="আবেদনটি সম্পাদনা করুণ"><i class="fa fa-edit"></i></a>
              </td>
            </tr>';
                }
            } else {
                $output = '
           <tr>
            <td align="center" colspan="6">পাওয়া যায়নি!</td>
           </tr>
           ';
            }
            $data = array(
                'table_data' => $output,
                'total_data' => $total_row . ' টি ফলাফল পাওয়া গেছে'
            );

            echo json_encode($data);
        }
    }

    public function getMembers(Request $request)
    {
        $memberscount = User::where('activation_status', 1)->where('role_type', '!=', 'admin')->count();
        $members = User::where('activation_status', 1)
            ->where('role_type', '!=', 'admin')
            ->orderBy('id', 'desc')->get();

        $ordered_member_array = [];
        foreach ($members as $member) {
            $ordered_member_array[(int)substr($member->member_id, -5)] = $member;
        }
        ksort($ordered_member_array); // ascending order according to key

        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $itemCollection = collect($ordered_member_array);

        $perPage = 20;

        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems = new LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        return view('dashboard.membership.members')
            ->withMembers($paginatedItems)
            ->withMemberscount($memberscount);
    }

    public function getMembersForAll(Request $request)
    {
        $memberscount = User::where('activation_status', 1)->where('role_type', '!=', 'admin')->count();
        $members = User::where('activation_status', 1)
            ->where('role_type', '!=', 'admin')
            ->orderBy('id', 'desc')->get();

        $ordered_member_array = [];
        foreach ($members as $member) {
            $ordered_member_array[(int)substr($member->member_id, -5)] = $member;
        }
        ksort($ordered_member_array); // ascending order according to key

        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $itemCollection = collect($ordered_member_array);

        $perPage = 20;

        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems = new LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        return view('dashboard.profile.membersforall')
            ->withMembers($paginatedItems)
            ->withMemberscount($memberscount);
    }

    public function getSearchMember()
    {
        return view('dashboard.membership.searchmember');
    }

    public function searchMemberAPI(Request $request)
    {
        $response = User::select('name_bangla', 'member_id', 'mobile', 'unique_key')
            ->where('activation_status', 1)
            ->where('role_type', '!=', 'admin') // avoid the super admin type
            ->orderBy('id', 'desc')->get();

        return $response;
    }

    public function searchMemberAPI2(Request $request)
    {
        if ($request->ajax()) {
            $output = '';
            $query = $request->get('query');
            if ($query != '') {
                $data = User::where('activation_status', 1)
                    ->where('role_type', '!=', 'admin') // avoid the super admin type
                    ->where(function ($newquery) use ($query) {
                        $newquery->where('name', 'like', '%' . $query . '%')
                            ->orWhere('name_bangla', 'like', '%' . $query . '%')
                            ->orWhere('member_id', 'like', '%' . $query . '%')
                            ->orWhere('mobile', 'like', '%' . $query . '%')
                            ->orWhere('email', 'like', '%' . $query . '%');
                    })
                    ->with('branch')
                    ->with('position')
                    ->orderBy('id', 'desc')
                    ->get();
            }

            $total_row = count($data);
            if ($total_row > 0) {
                foreach ($data as $row) {
                    $output .= '
            <tr>
             <td>
                <a href="' . route('dashboard.singlemember', $row->unique_key) . '" title="সদস্য তথ্য দেখুন">
                  ' . $row->name_bangla . '<br/> ' . $row->name . '
                </a>
             </td>
             <td><big><b>' . $row->member_id . '</big></b></td>
             <td>' . $row->mobile . '<br/>' . $row->email . '</td>
             <td>
                <a href="' . route('dashboard.branch.members', $row->branch->id) . '" title="সদস্য তথ্য দেখুন">
                  ' . $row->branch->name . '
                </a><br/>
                ' . $row->profession . ' (<a href="' . route('dashboard.designation.members', $row->position->id) . '" title="সদস্য তথ্য দেখুন">
                  ' . $row->position->name . '
                </a>)
            </td>
            ';
                    if ($row->image != null) {
                        $output .= '<td><img src="' . asset('images/users/' . $row->image) . '" style="height: 50px; width: auto;" /></td>';
                    } else {
                        $output .= '<td><img src="' . asset('images/user.png') . '" style="height: 50px; width: auto;" /></td>';
                    }
                    $output .= '<td><a class="btn btn-sm btn-success" href="' . route('dashboard.singlemember', $row->unique_key) . '" title="সদস্য তথ্য দেখুন"><i class="fa fa-eye"></i></a>
                <a class="btn btn-sm btn-primary" href="' . route('dashboard.singleapplicationedit', $row->unique_key) . '" title="আবেদনটি সম্পাদনা করুণ"><i class="fa fa-edit"></i></a>
              </td>
            </tr>';
                }
            } else {
                $output = '
           <tr>
            <td align="center" colspan="6">পাওয়া যায়নি!</td>
           </tr>
           ';
            }
            $data = array(
                'table_data' => $output,
                'total_data' => $total_row . ' টি ফলাফল পাওয়া গেছে'
            );

            echo json_encode($data);
        }
    }

    public function searchMemberAPI3(Request $request)
    {
        if ($request->ajax()) {
            $output = '';
            $query = $request->get('query');
            if ($query != '') {
                $data = User::where('activation_status', 1)
                    ->where('role_type', '!=', 'admin') // avoid the super admin type
                    ->where(function ($newquery) use ($query) {
                        $newquery->where('name', 'like', '%' . $query . '%')
                            ->orWhere('name_bangla', 'like', '%' . $query . '%')
                            ->orWhere('member_id', 'like', '%' . $query . '%')
                            ->orWhere('mobile', 'like', '%' . $query . '%')
                            ->orWhere('email', 'like', '%' . $query . '%');
                    })
                    ->with('branch')
                    ->with('position')
                    ->orderBy('id', 'desc')
                    ->get();
            }

            $total_row = count($data);
            if ($total_row > 0) {
                foreach ($data as $row) {
                    $output .= '
            <tr>
             <td>' . $row->name_bangla . '<br/> ' . $row->name . '</td>
             <td><big><b>' . $row->member_id . '</big></b></td>
             <td>' . $row->mobile . '<br/>' . $row->email . '</td>
             <td>' . $row->branch->name . '<br/>' . $row->profession . ' (' . $row->position->name . ')</td>
            ';
                    if ($row->image != null) {
                        $output .= '<td><img src="' . asset('images/users/' . $row->image) . '" style="height: 50px; width: auto;" /></td>';
                    } else {
                        $output .= '<td><img src="' . asset('images/user.png') . '" style="height: 50px; width: auto;" /></td>';
                    }
                    $output .= '</tr>';
                }
            } else {
                $output = '
           <tr>
            <td align="center" colspan="6">পাওয়া যায়নি!</td>
           </tr>
           ';
            }
            $data = array(
                'table_data' => $output,
                'total_data' => $total_row . ' টি ফলাফল পাওয়া গেছে'
            );

            echo json_encode($data);
        }
    }

    public function getSingleMember($unique_key)
    {
        $member = User::where('unique_key', $unique_key)->first();
        $pendingfordashboard = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 0)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->first();
        $approvedfordashboard = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->first();
        $totalmontlypaid = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->where('is_archieved', 0)
            ->where('payment_category', 1) // 1 means monthly, 0 for membership
            ->where('member_id', $member->member_id)
            ->first();
        $pendingcountdashboard = Payment::where('payment_status', 0)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->get()
            ->count();
        $approvedcountdashboard = Payment::where('payment_status', 1)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->get()
            ->count();

        $members = User::all();

        // for now, user can only see his profile, so if there is a change, then kaaj kora jaabe...
        if ((Auth::user()->role == 'member') && ($member->unique_key != Auth::user()->unique_key)) {
            Session::flash('warning', ' দুঃখিত, আপনার এই পাতাটি দেখার অনুমতি নেই!');
            return redirect()->route('dashboard.memberpayment');
        }

        return view('dashboard.membership.singlemember')
            ->withMember($member)
            ->withMembers($members)
            ->withPendingfordashboard($pendingfordashboard)
            ->withApprovedfordashboard($approvedfordashboard)
            ->withTotalmontlypaid($totalmontlypaid)
            ->withPendingcountdashboard($pendingcountdashboard)
            ->withApprovedcountdashboard($approvedcountdashboard);
    }

    public function getFormMessages()
    {
        $messages = Formmessage::orderBy('id', 'desc')->paginate(10);

        return view('dashboard.formmessage')
            ->withMessages($messages);
    }


    public function deleteFormMessage($id)
    {
        $messages = Formmessage::find($id);
        $messages->delete();

        Session::flash('success', 'Deleted Successfully!');
        return redirect()->route('dashboard.formmessage');
    }

    public function getProfile()
    {
        $positions = Position::where('id', '>', 0)->get();
        $branches = Branch::where('id', '>', 0)->get();
        $member = User::find(Auth::user()->id);
        $upazillas = Upazilla::groupBy('district_bangla')
            ->get();

        $pendingfordashboard = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 0)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->first();
        $approvedfordashboard = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->first();
        $pendingcountdashboard = Payment::where('payment_status', 0)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->get()
            ->count();
        $approvedcountdashboard = Payment::where('payment_status', 1)
            ->where('is_archieved', 0)
            ->where('member_id', $member->member_id)
            ->get()
            ->count();

        return view('dashboard.profile.index')
            ->withPositions($positions)
            ->withBranches($branches)
            ->withMember($member)
            ->withPendingfordashboard($pendingfordashboard)
            ->withApprovedfordashboard($approvedfordashboard)
            ->withPendingcountdashboard($pendingcountdashboard)
            ->withApprovedcountdashboard($approvedcountdashboard)
            ->withUpazillas($upazillas);

    }

    public function updateMemberProfile(Request $request, $id)
    {
        $this->validate($request, array(
            'position_id' => 'required',
            'branch_id' => 'required',
            'start_date' => 'sometimes',
            'present_address' => 'required',
            'mobile' => 'required',
            'email' => 'required',
            'blood_group' => 'sometimes',
            'upazilla_id' => 'sometimes| numeric',
            'prl_date' => 'sometimes',
            'image' => 'sometimes|image|max:250',
            'application_hard_copy' => 'sometimes|image|max:4096',
            'digital_signature' => 'sometimes|image|max:250',
        ));

        $member = User::find($id);

        if ($request->mobile != $member->mobile) {
            $findmobileuser = User::where('mobile', $request->mobile)->first();

            if ($findmobileuser) {
                Session::flash('warning', 'দুঃখিত! মোবাইল নম্বরটি ব্যবহৃত হয়ে গেছে; আরেকটি দিন');
                if ($member->id == Auth::user()->id) {
                    return redirect()->route('dashboard.profile');
                } else {
                    return redirect()->back();
                }
            }
        }
        if ($request->email != $member->email) {
            $findemailuser = User::where('email', $request->email)->first();

            if ($findemailuser) {
                Session::flash('warning', 'দুঃখিত! ইমেইলটি ব্যবহৃত হয়ে গেছে; আরেকটি দিন');
                if ($member->id == Auth::user()->id) {
                    return redirect()->route('dashboard.profile');
                } else {
                    return redirect()->back();
                }
            }
        }

        // check if any data is changed...
        if ((Auth::user()->position_id == $request->position_id) &&
            (Auth::user()->branch_id == $request->branch_id) &&
            (Auth::user()->present_address == $request->present_address) &&
            (Auth::user()->mobile == $request->mobile) &&
            (Auth::user()->email == $request->email) &&
            ($request->has('blood_group') && Auth::user()->blood_group == $request->blood_group) &&
            ($request->has('upazilla_id') && Auth::user()->upazilla_id == $request->upazilla_id) &&
            ($request->has('prl_date') && Auth::user()->prl_date == $request->prl_date) &&
            (Auth::user()->email == $request->email) &&
            !$request->hasFile('image') &&
            !$request->hasFile('application_hard_copy') &&
            !$request->hasFile('digital_signature')) {
            Session::flash('info', 'আপনি কোন তথ্য পরিবর্তন করেননি!');
            return redirect()->route('dashboard.profile');
        }


        // update data accordign Tempmemdatato role...
        if (Auth::user()->role != 'admin') {
            $tempmemdata = new Tempmemdata;
            $tempmemdata->user_id = $member->id;
            $tempmemdata->position_id = $request->position_id;
            $tempmemdata->branch_id = $request->branch_id;
            $tempmemdata->present_address = $request->present_address;
            $tempmemdata->mobile = $request->mobile;
            $tempmemdata->email = $request->email;

            if ($request->has('blood_group')) {
                $tempmemdata->blood_group = $request->blood_group;
            }
            if ($request->has('upazilla_id')) {
                $tempmemdata->upazilla_id = $request->upazilla_id;
            }
            if ($request->has('prl_date')) {
                $tempmemdata->prl_date = Carbon::parse($request->prl_date);
            }

            //check if career info changed and start_date not provided

            if (Auth::user()->position_id != $request->position_id || Auth::user()->branch_id != $request->branch_id) {

//                dd(DateTime::createFromFormat('d-m-Y', $request->start_date));
                if (!$request->has('start_date') || DateTime::createFromFormat('d-m-Y', $request->start_date) == false) {
                    Session::flash('warning', 'আপনি নতুন পদবি/দপ্তর এ যোগদানের তারিখ দেননি!');
                    if ($member->id == Auth::user()->id) {
                        return redirect()->route('dashboard.profile');
                    } else {
                        return redirect()->back();
                    }
                }
                $tempmemdata->start_date = Carbon::parse($request->start_date);
            }


            // applicant's temp image upload
            if ($request->hasFile('image')) {
                // $old_img = public_path('images/users/'. $application->image);
                // if(File::exists($old_img)) {
                //     File::delete($old_img);
                // }
                $image = $request->file('image');
                $filename = 'temp_' . str_replace(' ', '', $member->name) . time() . '.' . $image->getClientOriginalExtension();
                $location = public_path('/images/users/' . $filename);
                Image::make($image)->resize(200, 200)->save($location);
                $tempmemdata->image = $filename;
            }

            if ($request->hasFile('application_hard_copy')) {
                // $old_img = public_path('images/users/'. $application->image);
                // if(File::exists($old_img)) {
                //     File::delete($old_img);
                // }
                $image = $request->file('application_hard_copy');
                $filename = 'temp_application_hard_copy' . str_replace(' ', '', $member->name) . time() . '.' . $image->getClientOriginalExtension();
                $location = public_path('/images/users/' . $filename);
                Image::make($image)->save($location);
                $tempmemdata->application_hard_copy = $filename;
            }

            if ($request->hasFile('digital_signature')) {
                // $old_img = public_path('images/users/'. $application->image);
                // if(File::exists($old_img)) {
                //     File::delete($old_img);
                // }
                $image = $request->file('digital_signature');
                $filename = 'temp_digital_signature' . str_replace(' ', '', $member->name) . time() . '.' . $image->getClientOriginalExtension();
                $location = public_path('/images/users/' . $filename);
                Image::make($image)->resize(200, 200)->save($location);
                $tempmemdata->digital_signature = $filename;
            }


            $tempmemdata->save();

            Session::flash('success', 'আপনার তথ্য পরিবর্তন অনুরোধ সফলভাবে করা হয়েছে। আমাদের একজন প্রতিনিধি তা অনুমোদন করবেন। ধন্যবাদ।');
            return redirect()->route('dashboard.profile');
        } else {


            //check if career info changed and start_date not provided
            if (Auth::user()->position_id != $request->position_id || Auth::user()->branch_id != $request->branch_id) {

                if (!$request->has('start_date') || DateTime::createFromFormat('d-m-Y', $request->start_date) == false) {
                    Session::flash('warning', 'আপনি নতুন পদবি/দপ্তর এ যোগদানের তারিখ দেননি!');
                    if ($member->id == Auth::user()->id) {
                        return redirect()->route('dashboard.profile');
                    } else {
                        return redirect()->back();
                    }
                }
                $newCareerLog = new Careerlog();
                $newCareerLog->user_id = $member->id;
                $newCareerLog->branch_id = $request->branch_id;
                $newCareerLog->position_id = $request->position_id;
                $newCareerLog->start_date = Carbon::parse($request->start_date);
                $newCareerLog->prev_branch_name = ($member->branch_id != 0) ? $member->branch->name : $member->office;
                $newCareerLog->prev_position_name = ($member->position_id != 0) ? $member->position->name : $member->designation;
                $newCareerLog->save();
            }


            $member->position_id = $request->position_id;
            $member->branch_id = $request->branch_id;
            $member->present_address = $request->present_address;
            $member->mobile = $request->mobile;
            $member->email = $request->email;

            if ($request->has('blood_group')) {
                $member->blood_group = $request->blood_group;
            }
            if ($request->has('upazilla_id')) {
                $member->upazilla_id = $request->upazilla_id;
            }

            // applicant's temp image upload
            if ($request->hasFile('image')) {
                $old_img = public_path('images/users/' . $member->image);
                if (File::exists($old_img)) {
                    File::delete($old_img);
                }
                $image = $request->file('image');
                $filename = str_replace(' ', '', $member->name) . time() . '.' . $image->getClientOriginalExtension();
                $location = public_path('/images/users/' . $filename);
                Image::make($image)->resize(200, 200)->save($location);
                $member->image = $filename;
            }

            if ($request->hasFile('application_hard_copy')) {
                // $old_img = public_path('images/users/'. $application->image);
                // if(File::exists($old_img)) {
                //     File::delete($old_img);
                // }
                $image = $request->file('application_hard_copy');
                $filename = 'application_hard_copy' . str_replace(' ', '', $member->name) . time() . '.' . $image->getClientOriginalExtension();
                $location = public_path('/images/users/' . $filename);
                Image::make($image)->resize(200, 200)->save($location);
                $member->application_hard_copy = $filename;
            }

            if ($request->hasFile('digital_signature')) {
                // $old_img = public_path('images/users/'. $application->image);
                // if(File::exists($old_img)) {
                //     File::delete($old_img);
                // }
                $image = $request->file('digital_signature');
                $filename = 'digital_signature' . str_replace(' ', '', $member->name) . time() . '.' . $image->getClientOriginalExtension();
                $location = public_path('/images/users/' . $filename);
                Image::make($image)->resize(200, 200)->save($location);
                $member->digital_signature = $filename;
            }


            $member->save();

            Session::flash('success', 'সফলভাবে হালনাগাদ করা হয়েছে!');
            return redirect()->back();
        }
    }

    public function getMembersUpdateRequests()
    {
        $tempmemdatas = Tempmemdata::orderBy('id', 'desc')->paginate(10);

        return view('dashboard.membership.membersupdaterequests')
            ->withTempmemdatas($tempmemdatas);
    }

    public function approveUpdateRequest(Request $request)
    {
        $tempmemdata = Tempmemdata::where('id', $request->tempmemdata_id)->first();
        $member = User::where('id', $request->user_id)->first();

        //create Career log entry if position/branch changes
        if ($tempmemdata->start_date && ($member->position_id != $tempmemdata->position_id || $member->branch_id != $tempmemdata->branch_id)) {
            $newCareerLog = new Careerlog();
            $newCareerLog->user_id = $member->id;
            $newCareerLog->branch_id = $tempmemdata->branch_id;
            $newCareerLog->position_id = $tempmemdata->position_id;
            $newCareerLog->start_date = $tempmemdata->start_date;
            $newCareerLog->prev_branch_name = ($member->branch_id != 0) ? $member->branch->name : $member->office;
            $newCareerLog->prev_position_name = ($member->position_id != 0) ? $member->position->name : $member->designation;
            $newCareerLog->save();
        }


        $member->position_id = $tempmemdata->position_id;
        $member->branch_id = $tempmemdata->branch_id;
        $member->present_address = $tempmemdata->present_address;
        $member->mobile = $tempmemdata->mobile;
        $member->email = $tempmemdata->email;
        $member->blood_group = $tempmemdata->blood_group;
        $member->upazilla_id = $tempmemdata->upazilla_id;
        $member->prl_date = $tempmemdata->prl_date;


        // applicant's temp image upload
        if ($tempmemdata->image != '') {
            $old_img = public_path('images/users/' . $member->image);
            if (File::exists($old_img)) {
                File::delete($old_img);
            }
            $member->image = $tempmemdata->image;
        }

        if ($tempmemdata->application_hard_copy != null) {
            $old_img = public_path('images/users/' . $member->application_hard_copy);
            if (File::exists($old_img)) {
                File::delete($old_img);
            }
            $member->application_hard_copy = $tempmemdata->application_hard_copy;
        }

        if ($tempmemdata->digital_signature != null) {
            $old_img = public_path('images/users/' . $member->digital_signature);
            if (File::exists($old_img)) {
                File::delete($old_img);
            }
            $member->digital_signature = $tempmemdata->digital_signature;
        }
        $member->save();
        $this->addToAdminLog($member, 'update_member', 'সদস্য তথ্য দাখিল', []);

        $tempmemdata->delete();


        // send sms
        $mobile_number = 0;
        if (strlen($member->mobile) == 11) {
            $mobile_number = $member->mobile;
        } elseif (strlen($member->mobile) > 11) {
            if (strpos($member->mobile, '+') !== false) {
                $mobile_number = substr($member->mobile, -11);
            }
        }
        $url = config('sms.url');
        $number = $mobile_number;
        $text = 'Dear ' . $member->name . ', your information changing request has been approved! Thanks. Customs and VAT Co-operative Society (CVCS). Login: https://cvcsbd.com/login';
        // this sms costs 2 SMS

        $data = array(
            'username' => config('sms.username'),
            'password' => config('sms.password'),
            'number' => "$number",
            'message' => "$text",
        );

        // initialize send status
        $ch = curl_init(); // Initialize cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this is important
        $smsresult = curl_exec($ch);

        $p = explode("|", $smsresult);
        $sendstatus = $p[0];
        // send sms
        if ($sendstatus == 1101) {
            Session::flash('info', 'SMS সফলভাবে পাঠানো হয়েছে!');
        } elseif ($sendstatus == 1006) {
            Session::flash('warning', 'অপর্যাপ্ত SMS ব্যালেন্সের কারণে SMS পাঠানো যায়নি!');
        } else {
            Session::flash('warning', 'দুঃখিত! SMS পাঠানো যায়নি!');
        }

        Session::flash('success', 'সফলভাবে হালনাগাদ করা হয়েছে!');
        return redirect()->route('dashboard.membersupdaterequests');
    }

    public function deleteUpdateRequest($id)
    {
        $tempmemdata = Tempmemdata::find($id);
        $image_path = public_path('images/users/' . $tempmemdata->image);
        if (File::exists($image_path)) {
            File::delete($image_path);
        }
        $tempmemdata->delete();

        Session::flash('success', 'সফলভাবে ডিলেট করে দেওয়া হয়েছে!');
        return redirect()->route('dashboard.membersupdaterequests');
    }

    public function getMemberChangePassword()
    {
        return view('dashboard.profile.changepassword');
    }

    public function memberChangePassword(Request $request)
    {
        $this->validate($request, array(
            'oldpassword' => 'required',
            'newpassword' => 'required|min:8',
            'againnewpassword' => 'required|same:newpassword'
        ));

        $member = User::find(Auth::user()->id);

        if (Hash::check($request->oldpassword, $member->password)) {
            $member->password = Hash::make($request->newpassword);
            $member->save();
            Session::flash('success', 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে!');
            return redirect()->route('dashboard.profile');
        } else {
            Session::flash('warning', 'পুরোনো পাসওয়ার্ডটি সঠিক নয়!');
            return redirect()->route('dashboard.member.getchangepassword');
        }
    }

    public function getPaymentPage()
    {
        $payments = Payment::where('member_id', Auth::user()->member_id)
            ->where('is_archieved', 0)
            ->orderBy('id', 'desc')
            ->paginate(10);
        $members = User::all();

        return view('dashboard.profile.payment')
            ->withPayments($payments)
            ->withMembers($members);
    }

    public function getSelfPaymentPage()
    {
        return view('dashboard.profile.selfpayment');
    }

    public function storeSelfPayment(Request $request)
    {
        $this->validate($request, array(
            'member_id' => 'required',
            'amount' => 'required|integer',
            'bank' => 'required',
            'branch' => 'required',
            'pay_slip' => 'required',
            'image' => 'sometimes|image|max:500'
        ));

        $payment = new Payment;
        $payment->member_id = $request->member_id;
        $payment->payer_id = $request->member_id;
        $payment->amount = $request->amount;
        $payment->bank = $request->bank;
        $payment->branch = $request->branch;
        $payment->pay_slip = $request->pay_slip;
        $payment->payment_status = 0;
        $payment->payment_category = 1; // monthly payment, if 0 then membership payment
        $payment->payment_type = 1; // single payment, if 2 then bulk payment
        // generate payment_key
        $payment_key_length = 10;
        $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $payment_key = substr(str_shuffle(str_repeat($pool, 10)), 0, $payment_key_length);
        // generate payment_key
        $payment->payment_key = $payment_key;
        $payment->save();


        // receipt upload
        if ($request->hasFile('image')) {
            $receipt = $request->file('image');
            $filename = $payment->member_id . '_receipt_' . time() . '.' . $receipt->getClientOriginalExtension();
            $location = public_path('/images/receipts/' . $filename);
            Image::make($receipt)->resize(800, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($location);
            $paymentreceipt = new Paymentreceipt;
            $paymentreceipt->payment_id = $payment->id;
            $paymentreceipt->image = $filename;
            $paymentreceipt->save();
        }

        // send pending SMS ... aro kichu kaaj baki ache...
        // send sms
        $mobile_number = 0;
        if (strlen(Auth::user()->mobile) == 11) {
            $mobile_number = Auth::user()->mobile;
        } elseif (strlen(Auth::user()->mobile) > 11) {
            if (strpos(Auth::user()->mobile, '+') !== false) {
                $mobile_number = substr(Auth::user()->mobile, -11);
            }
        }
        $url = config('sms.url');
        $number = $mobile_number;
        $text = 'Dear ' . Auth::user()->name . ', payment of tk. ' . $request->amount . ' is submitted successfully. We will notify you once we approve it. Customs and VAT Co-operative Society (CVCS). Login: https://cvcsbd.com/login';
        $data = array(
            'username' => config('sms.username'),
            'password' => config('sms.password'),
            'number' => "$number",
            'message' => "$text"
        );
        // initialize send status
        $ch = curl_init(); // Initialize cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this is important
        $smsresult = curl_exec($ch);

        // $sendstatus = $result = substr($smsresult, 0, 3);
        $p = explode("|", $smsresult);
        $sendstatus = $p[0];
        // send sms
        if ($sendstatus == 1101) {
            // Session::flash('info', 'SMS সফলভাবে পাঠানো হয়েছে!');
        } elseif ($sendstatus == 1006) {
            // Session::flash('warning', 'অপর্যাপ্ত SMS ব্যালেন্সের কারণে SMS পাঠানো যায়নি!');
        } else {
            // Session::flash('warning', 'দুঃখিত! SMS পাঠানো যায়নি!');
        }

        Session::flash('success', 'পরিশোধ সফলভাবে দাখিল করা হয়েছে!');
        return redirect()->route('dashboard.memberpayment');
    }

    public function downloadMemberPaymentPDF(Request $request)
    {
        $this->validate($request, array(
            'id' => 'required',
            'payment_key' => 'required'
        ));

        $payment = Payment::where('id', $request->id)
            ->where('payment_key', $request->payment_key)
            ->first();

        $pdf = PDF::loadView('dashboard.profile.pdf.paymentreportsingle', ['payment' => $payment]);
        $fileName = 'Payment_Report_' . Auth::user()->member_id . '_' . $payment->payment_key . '.pdf';
        return $pdf->download($fileName);
    }

//    public function downloadMemberCompletePDF(Request $request)
//    {
//        $this->validate($request, array(
//            'id' => 'required',
//            'member_id' => 'required'
//        ));
//
//        $member = User::where('id', $request->id)
//            ->where('member_id', $request->member_id)
//            ->first();
//
//        $payments = Payment::where('member_id', $request->member_id)
//            ->where('is_archieved', 0)
//            ->get();
//
//        $pendingfordashboard = DB::table('payments')
//            ->select(DB::raw('SUM(amount) as totalamount'))
//            ->where('payment_status', 0)
//            ->where('is_archieved', 0)
//            ->where('member_id', $member->member_id)
//            ->first();
//        $approvedfordashboard = DB::table('payments')
//            ->select(DB::raw('SUM(amount) as totalamount'))
//            ->where('payment_status', 1)
//            ->where('is_archieved', 0)
//            ->where('member_id', $member->member_id)
//            ->first();
//        $pendingcountdashboard = Payment::where('payment_status', 0)
//            ->where('is_archieved', 0)
//            ->where('member_id', $member->member_id)
//            ->get()
//            ->count();
//
//        $approvedcountdashboard = Payment::where('payment_status', 1)
//            ->where('is_archieved', 0)
//            ->where('member_id', $member->member_id)
//            ->get()
//            ->count();
//        $totalmontlypaid = DB::table('payments')
//            ->select(DB::raw('SUM(amount) as totalamount'))
//            ->where('payment_status', 1)
//            ->where('is_archieved', 0)
//            ->where('payment_category', 1) // 1 means monthly, 0 for membership
//            ->where('member_id', $member->member_id)
//            ->first();
//
//        $pdf = PDF::loadView('dashboard.profile.pdf.completereport', ['payments' => $payments, 'member' => $member, 'pendingfordashboard' => $pendingfordashboard, 'approvedfordashboard' => $approvedfordashboard, 'pendingcountdashboard' => $pendingcountdashboard, 'approvedcountdashboard' => $approvedcountdashboard, 'totalmontlypaid' => $totalmontlypaid]);
//        $fileName = str_replace(' ', '_', $member->name) . '_' . $member->member_id . '.pdf';
//        return $pdf->download($fileName);
//    }

    public function getMemberTransactionSummary()
    {
        $membertotalpending = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('member_id', Auth::user()->member_id)
            ->where('payment_status', 0)
            ->where('is_archieved', 0)
            // ->where(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), "=", Carbon::now()->format('Y-m'))
            // ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->first();

        $membertotalapproved = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('member_id', Auth::user()->member_id)
            ->where('payment_status', '=', 1)
            ->where('is_archieved', '=', 0)
            // ->where(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), "=", Carbon::now()->format('Y-m'))
            // ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->first();
        $membertotalmontlypaid = DB::table('payments')
            ->select(DB::raw('SUM(amount) as totalamount'))
            ->where('payment_status', 1)
            ->where('is_archieved', 0)
            ->where('payment_category', 1) // 1 means monthly, 0 for membership
            ->where('member_id', Auth::user()->member_id)
            ->first();

        return view('dashboard.profile.transactionsummary')
            ->withMembertotalpending($membertotalpending)
            ->withMembertotalapproved($membertotalapproved)
            ->withMembertotalmontlypaid($membertotalmontlypaid);
    }

    public function getMemberUserManual()
    {
        return view('dashboard.profile.usermanual');
    }

    public function storeBulkPayment(Request $request)
    {
        $this->validate($request, array(
            'amount' => 'required|integer',
            'bank' => 'required',
            'branch' => 'required',
            'pay_slip' => 'required',
            'image1' => 'required|image|max:500',
            'image2' => 'sometimes|image|max:500',
            'image3' => 'sometimes|image|max:500'
        ));

        // dd($request->all());
        $payment = new Payment;
        $payment->member_id = Auth::user()->member_id;
        $payment->payer_id = Auth::user()->member_id;
        $payment->amount = $request->amount;
        $payment->bank = $request->bank;
        $payment->branch = $request->branch;
        $payment->pay_slip = $request->pay_slip;
        $payment->payment_status = 0;
        $payment->payment_category = 1; // monthly payment
        $payment->payment_type = 2; // bulk payment
        // generate payment_key
        $payment_key_length = 10;
        $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $payment_key = substr(str_shuffle(str_repeat($pool, 10)), 0, $payment_key_length);
        // generate payment_key
        $payment->payment_key = $payment_key;

        // storing bulk ids and amounts
        $amountids = $request->amountids;
        $amount_id = [];
        foreach ($amountids as $amountid) {
            $amount_id[$amountid] = $request['amount' . $amountid];
        }
        $payment->bulk_payment_member_ids = json_encode($amount_id);

        $payment->save();

        // receipt upload
        for ($itrt = 1; $itrt < 4; $itrt++) {
            if ($request->hasFile('image' . $itrt)) {
                $receipt = $request->file('image' . $itrt);
                $filename = $payment->member_id . $itrt . '_receipt_' . time() . '.' . $receipt->getClientOriginalExtension();
                $location = public_path('/images/receipts/' . $filename);
                Image::make($receipt)->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($location);
                $paymentreceipt = new Paymentreceipt;
                $paymentreceipt->payment_id = $payment->id;
                $paymentreceipt->image = $filename;
                $paymentreceipt->save();
            }
        }


        // send sms
        // $mobile_numbers = [];
        $smssuccesscount = 0;
        $url = config('sms.url');

        $multiCurl = array();
        // data to be returned
        $result = array();
        // multi handle
        $mh = curl_multi_init();
        // sms data
        $smsdata = [];

        $members = User::whereIn('member_id', $amountids)->get();
        foreach ($members as $i => $member) {
            $mobile_number = 0;
            if (strlen($member->mobile) == 11) {
                $mobile_number = $member->mobile;
            } elseif (strlen($member->mobile) > 11) {
                if (strpos($member->mobile, '+') !== false) {
                    $mobile_number = substr($member->mobile, -11);
                }
            }
            // if($mobile_number != 0) {
            //   array_push($mobile_numbers, $mobile_number);
            // }
            $text = 'Dear ' . $member->name . ', a payment is submitted against your account. We will notify you further updates. Customs and VAT Co-operative Society (CVCS). Login: https://cvcsbd.com/login';
            $smsdata[$i] = array(
                'username' => config('sms.username'),
                'password' => config('sms.password'),
                // 'apicode'=>"1",
                'number' => "$mobile_number",
                // 'msisdn'=>"$mobile_number",
                // 'countrycode'=>"880",
                // 'cli'=>"CVCS",
                // 'messagetype'=>"1",
                'message' => "$text",
                // 'messageid'=>"1"
            );
            $multiCurl[$i] = curl_init(); // Initialize cURL
            curl_setopt($multiCurl[$i], CURLOPT_URL, $url);
            curl_setopt($multiCurl[$i], CURLOPT_HEADER, 0);
            curl_setopt($multiCurl[$i], CURLOPT_POSTFIELDS, http_build_query($smsdata[$i]));
            curl_setopt($multiCurl[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($multiCurl[$i], CURLOPT_SSL_VERIFYPEER, false); // this is important
            curl_multi_add_handle($mh, $multiCurl[$i]);
        }

        $index = null;
        do {
            curl_multi_exec($mh, $index);
        } while ($index > 0);
        // get content and remove handles
        foreach ($multiCurl as $k => $ch) {
            $result[$k] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            // $sendstatus = substr($result[$k], 0, 3);
            $p = explode("|", $result[$k]);
            $sendstatus = $p[0];
            if ($sendstatus == 1101) {
                $smssuccesscount++;
            }
        }
        // close
        curl_multi_close($mh);


        Session::flash('success', 'পরিশোধ সফলভাবে দাখিল করা হয়েছে!');
        return redirect()->route('dashboard.memberpayment');
    }

    public function getBulkPaymentPage()
    {
        $branch = Branch::find(Auth::user()->branch->id);
        $members = User::where('activation_status', 1)
            ->where('role_type', '!=', 'admin')
            ->where('branch_id', Auth::user()->branch->id)
            ->orderBy('id', 'desc')
            ->get();
        return view('dashboard.adminsandothers.bulkpayment')
            ->withBranch($branch)
            ->withMembers($members);
    }

    public function getBulkPaymentPageFromBranch($branch_id)
    {
        $branch = Branch::find($branch_id);
        $members = User::where('activation_status', 1)
            ->where('role_type', '!=', 'admin')
            ->where('branch_id', $branch_id)
            ->orderBy('id', 'desc')
            ->get();
        return view('dashboard.adminsandothers.bulkpayment')
            ->withBranch($branch)
            ->withMembers($members);
    }

    public function searchMemberForBulkPaymentAPI(Request $request)
    {
        $response = User::select('name_bangla', 'member_id', 'mobile', 'position_id', 'joining_date')
            ->where('activation_status', 1)
            ->where('role_type', '!=', 'admin')
            ->with('position')
            ->with(['payments' => function ($query) {
                $query->orderBy('created_at', 'desc');
                $query->where('payment_status', '=', 1);
                $query->where('is_archieved', '=', 0);
                $query->where('payment_category', 1);  // 1 means monthly, 0 for membership
            }])
            ->orderBy('id', 'desc')->get();

        foreach ($response as $member) {
            $approvedcashformontly = $member->payments->sum('amount');
            $member->totalpendingmonthly = 0;
            if ($member->joining_date == '' || $member->joining_date == null || strtotime('31-01-2019') > strtotime($member->joining_date)) {
                $thismonth = Carbon::now()->format('Y-m-');
                $from = Carbon::createFromFormat('Y-m-d', '2019-1-1');
                $to = Carbon::createFromFormat('Y-m-d', $thismonth . '1');
                $totalmonthsformember = $to->diffInMonths($from) + 1;
                if (($totalmonthsformember * 500) > $approvedcashformontly) {
                    $member->totalpendingmonthly = ($totalmonthsformember * 500) - $approvedcashformontly;
                }
            } else {
                $startmonth = date('Y-m-', strtotime($member->joining_date));
                $thismonth = Carbon::now()->format('Y-m-');
                $from = Carbon::createFromFormat('Y-m-d', $startmonth . '1');
                $to = Carbon::createFromFormat('Y-m-d', $thismonth . '1');
                $totalmonthsformember = $to->diffInMonths($from) + 1;
                if (($totalmonthsformember * 500) > $approvedcashformontly) {
                    $member->totalpendingmonthly = ($totalmonthsformember * 500) - $approvedcashformontly;
                }
            }
        }

        return $response;
    }

    public function searchMemberForBulkPaymentSingleAPI($member_id)
    {
        $response = User::select('name_bangla', 'member_id', 'mobile', 'position_id', 'joining_date')
            ->where('activation_status', 1)
            ->where('member_id', $member_id)
            ->with('position')
            ->with(['payments' => function ($query) {
                $query->orderBy('created_at', 'desc');
                $query->where('payment_status', '=', 1);
                $query->where('is_archieved', '=', 0);
                $query->where('payment_category', 1);  // 1 means monthly, 0 for membership
            }])
            ->first();

        $approvedcashformontly = $response->payments->sum('amount');
        $response->totalpendingmonthly = 0;
        if ($response->joining_date == '' || $response->joining_date == null || strtotime('31-01-2019') > strtotime($response->joining_date)) {
            $thismonth = Carbon::now()->format('Y-m-');
            $from = Carbon::createFromFormat('Y-m-d', '2019-1-1');
            $to = Carbon::createFromFormat('Y-m-d', $thismonth . '1');
            $totalmonthsformember = $to->diffInMonths($from) + 1;
            if (($totalmonthsformember * 500) > $approvedcashformontly) {
                $response->totalpendingmonthly = ($totalmonthsformember * 500) - $approvedcashformontly;
            }
        } else {
            $startmonth = date('Y-m-', strtotime($response->joining_date));
            $thismonth = Carbon::now()->format('Y-m-');
            $from = Carbon::createFromFormat('Y-m-d', $startmonth . '1');
            $to = Carbon::createFromFormat('Y-m-d', $thismonth . '1');
            $totalmonthsformember = $to->diffInMonths($from) + 1;
            if (($totalmonthsformember * 500) > $approvedcashformontly) {
                $response->totalpendingmonthly = ($totalmonthsformember * 500) - $approvedcashformontly;
            }
        }

        return $response;
    }

    public function getMembersPendingPayments()
    {
        $payments = Payment::where('payment_status', 0)
            ->where('is_archieved', 0)
            ->orderBy('id', 'desc')
            ->paginate(10);
        $members = User::all();
        return view('dashboard.payments.pending')
            ->withPayments($payments)
            ->withMembers($members);
    }

    public function getMembersApprovedPayments()
    {
        $payments = Payment::where('payment_status', 1)
            ->where('is_archieved', 0)
            ->orderBy('id', 'desc')
            ->paginate(10);
        return view('dashboard.payments.approved')
            ->withPayments($payments);
    }

    public function approveSinglePayment(Request $request, $id)
    {
        $payment = Payment::find($id);

        $payment->payment_status = 1;
        $payment->save();
        $this->addToAdminLog($payment->user, 'approve_single_payment', 'সিঙ্গেল পেমেন্ট অনুমোদন', ['payment_id' => $payment->id]);

        // send pending SMS ... aro kichu kaaj baki ache...
        // send sms
        $mobile_number = 0;
        if (strlen($payment->user->mobile) == 11) {
            $mobile_number = $payment->user->mobile;
        } elseif (strlen($payment->user->mobile) > 11) {
            if (strpos($payment->user->mobile, '+') !== false) {
                $mobile_number = substr($payment->user->mobile, -11);
            }
        }
        $url = config('sms.url');
        $number = $mobile_number;
        $text = 'Dear ' . $payment->user->name . ', payment of tk. ' . $payment->amount . ' is APPROVED successfully! Thanks. Customs and VAT Co-operative Society (CVCS). Login: https://cvcsbd.com/login';
        $data = array(
            'username' => config('sms.username'),
            'password' => config('sms.password'),
            // 'apicode'=>"1",
            'number' => "$number",
            // 'msisdn'=>"$number",
            // 'countrycode'=>"880",
            // 'cli'=>"CVCS",
            // 'messagetype'=>"1",
            'message' => "$text",
            // 'messageid'=>"1"
        );
        // initialize send status
        $ch = curl_init(); // Initialize cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this is important
        $smsresult = curl_exec($ch);

        // $sendstatus = $result = substr($smsresult, 0, 3);
        $p = explode("|", $smsresult);
        $sendstatus = $p[0];
        // send sms
        if ($sendstatus == 1101) {
            Session::flash('info', 'SMS সফলভাবে পাঠানো হয়েছে!');
        } elseif ($sendstatus == 1006) {
            Session::flash('warning', 'অপর্যাপ্ত SMS ব্যালেন্সের কারণে SMS পাঠানো যায়নি!');
        } else {
            Session::flash('warning', 'দুঃখিত! SMS পাঠানো যায়নি!');
        }

        Session::flash('success', 'অনুমোদন সফল হয়েছে!');
        return redirect()->route('dashboard.membersapprovedpayments');
    }

    public function approveBulkPayment(Request $request, $id)
    {
        $bulkpayment = Payment::find($id);

        foreach (json_decode($bulkpayment->bulk_payment_member_ids) as $member_id => $amount) {
            $payment = new Payment;
            $payment->member_id = $member_id;
            $payment->payer_id = $bulkpayment->payer_id;
            $payment->amount = $amount;
            $payment->bank = $bulkpayment->bank;
            $payment->branch = $bulkpayment->branch;
            $payment->pay_slip = $bulkpayment->pay_slip;
            $payment->payment_status = 1; // approved
            $payment->payment_category = 1; // monthly payment
            $payment->payment_type = 2; // bulk payment
            $payment->payment_key = $bulkpayment->payment_key;
            $payment->save();

            $this->addToAdminLog($payment->user, 'bulk_payment_individual', 'বাল্ক পেমেন্ট', ['payment_id' => $payment->id]);


            // receipt upload
            if (count($bulkpayment->paymentreceipts) > 0) {
                foreach ($bulkpayment->paymentreceipts as $paymentreceipt) {
                    $newpaymentreceipt = new Paymentreceipt;
                    $newpaymentreceipt->payment_id = $payment->id;
                    $newpaymentreceipt->image = $paymentreceipt->image;
                    $newpaymentreceipt->save();
                }
            }
        }

        $bulkpayment->is_archieved = 1;
        $bulkpayment->save();
        $this->addToAdminLog($bulkpayment, 'approve_bulk_payment', 'বাল্ক পেমেন্ট অনুমোদন', []);


        // send sms
        // $mobile_numbers = [];
        $smssuccesscount = 0;
        $url = config('sms.url');

        $multiCurl = array();
        // data to be returned
        $result = array();
        // multi handle
        $mh = curl_multi_init();
        // sms data
        $smsdata = [];

        foreach (json_decode($bulkpayment->bulk_payment_member_ids) as $member_id => $amount) {
            $member = User::where('member_id', $member_id)->first();
            $mobile_number = 0;
            if (strlen($member->mobile) == 11) {
                $mobile_number = $member->mobile;
            } elseif (strlen($member->mobile) > 11) {
                if (strpos($member->mobile, '+') !== false) {
                    $mobile_number = substr($member->mobile, -11);
                }
            }
            // if($mobile_number != 0) {
            //   array_push($mobile_numbers, $mobile_number);
            // }
            $text = 'Dear ' . $member->name . ', payment of tk. ' . $amount . ' is APPROVED successfully! Thanks. Customs and VAT Co-operative Society (CVCS). Login: https://cvcsbd.com/login';
            $smsdata[$member_id] = array(
                'username' => config('sms.username'),
                'password' => config('sms.password'),
                // 'apicode'=>"1",
                'number' => "$mobile_number",
                // 'msisdn'=>"$mobile_number",
                // 'countrycode'=>"880",
                // 'cli'=>"CVCS",
                // 'messagetype'=>"1",
                'message' => "$text",
                // 'messageid'=>"2"
            );
            $multiCurl[$member_id] = curl_init(); // Initialize cURL
            curl_setopt($multiCurl[$member_id], CURLOPT_URL, $url);
            curl_setopt($multiCurl[$member_id], CURLOPT_HEADER, 0);
            curl_setopt($multiCurl[$member_id], CURLOPT_POSTFIELDS, http_build_query($smsdata[$member_id]));
            curl_setopt($multiCurl[$member_id], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($multiCurl[$member_id], CURLOPT_SSL_VERIFYPEER, false); // this is important
            curl_multi_add_handle($mh, $multiCurl[$member_id]);
        }

        $index = null;
        do {
            curl_multi_exec($mh, $index);
        } while ($index > 0);
        // get content and remove handles
        foreach ($multiCurl as $k => $ch) {
            $result[$k] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            $smsresult = $result[$k];
            $p = explode("|", $smsresult);
            $sendstatus = $p[0];
            if ($sendstatus == 1101) {
                $smssuccesscount++;
            }
        }
        // close
        curl_multi_close($mh);

        Session::flash('success', 'অনুমোদন সফল হয়েছে!');
        return redirect()->route('dashboard.membersapprovedpayments');
    }

    public function getNotifications()
    {
        return view('dashboard.notifications');
    }
}
