import dynamic from "next/dynamic";

const ModuleCards = dynamic(() => import("@/components/dashboard/ModuleCards"), { loading: () => <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">{[1,2,3].map(i => <div key={i} className="h-40 rounded-lg bg-gray-100 animate-pulse" />)}</div> });
const SalesProfit = dynamic(() => import("@/components/dashboard/SalesProfit"), { loading: () => <div className="h-[400px] rounded-lg bg-gray-100 animate-pulse" /> });
const TotalFollowers = dynamic(() => import("@/components/dashboard/TotalFollowers"), { loading: () => <div className="h-[140px] rounded-lg bg-gray-100 animate-pulse" /> });
const TotalIncome = dynamic(() => import("@/components/dashboard/TotalIncome"), { loading: () => <div className="h-[140px] rounded-lg bg-gray-100 animate-pulse" /> });
const PopularProducts = dynamic(() => import("@/components/dashboard/PopularProducts"), { loading: () => <div className="h-[300px] rounded-lg bg-gray-100 animate-pulse" /> });
const EarningReports = dynamic(() => import("@/components/dashboard/EarningReports"), { loading: () => <div className="h-[300px] rounded-lg bg-gray-100 animate-pulse" /> });

export default function DashboardPage() {
  return (
    <div className="space-y-6 lg:space-y-8">
      <ModuleCards />
      <div className="grid grid-cols-12 gap-6 lg:gap-8">
        <div className="lg:col-span-8 col-span-12">
          <SalesProfit />
        </div>
        <div className="lg:col-span-4 col-span-12">
          <div className="grid grid-cols-12 gap-6 lg:gap-8">
            <div className="col-span-12">
              <TotalFollowers />
            </div>
            <div className="col-span-12">
              <TotalIncome />
            </div>
          </div>
        </div>
        <div className="lg:col-span-8 col-span-12">
          <PopularProducts />
        </div>
        <div className="lg:col-span-4 col-span-12">
          <EarningReports />
        </div>
      </div>
    </div>
  );
}
