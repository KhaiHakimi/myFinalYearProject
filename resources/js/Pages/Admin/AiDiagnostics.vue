<template>
    <Head title="Algorithm Diagnostics Matrix" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2 md:gap-4">
                <Link :href="route('admin.analytics_page')" class="text-indigo-600 hover:text-indigo-800 transition">
                    <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl md:text-3xl font-extrabold leading-tight text-gray-900">
                    Algorithm Diagnostics Matrix
                </h2>
            </div>
        </template>

        <div class="py-6 md:py-12 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6 md:space-y-12">
                
                <!-- Evaluation Matrix -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden animate-fade-in-down">
                    <div class="p-4 md:p-6 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-lg md:text-2xl font-bold text-gray-900 flex items-center gap-2 md:gap-3">
                            <svg class="w-6 h-6 md:w-8 md:h-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            1. Evaluation Matrix (Accuracy Testing)
                        </h3>
                    </div>
                    <div class="p-4 md:p-8 grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-10">
                        
                        <!-- RF Evaluation -->
                        <div>
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 md:mb-5 border-b border-gray-100 pb-3 gap-2">
                                <h4 class="text-indigo-600 font-bold text-sm md:text-base uppercase tracking-wider">Random Forest (Safety Predictor)</h4>
                                <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-3 py-1 rounded-full text-[10px] md:text-xs font-black uppercase shadow-sm self-start sm:self-auto">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Performance: Optimal
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3 md:gap-4 mb-6 md:mb-8">
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 md:p-5">
                                    <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-1 md:mb-2">Accuracy</div>
                                    <div class="text-2xl md:text-4xl font-black text-emerald-600 flex flex-col sm:flex-row sm:items-end gap-1 sm:gap-2">
                                        {{ (diagnostics.evaluation.random_forest.accuracy * 100).toFixed(1) }}%
                                        <span class="text-xs md:text-sm text-emerald-500 flex items-center mb-1 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">
                                            <svg class="w-3 h-3 md:w-4 md:h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                            {{ rfTrend }}
                                        </span>
                                    </div>
                                </div>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 md:p-5">
                                    <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-1 md:mb-2">F1-Score</div>
                                    <div class="text-2xl md:text-4xl font-black text-cyan-600">{{ diagnostics.evaluation.random_forest.f1_score.toFixed(2) }}</div>
                                </div>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 md:p-5">
                                    <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-1 md:mb-2">Precision</div>
                                    <div class="text-2xl md:text-4xl font-black text-blue-600">{{ diagnostics.evaluation.random_forest.precision.toFixed(2) }}</div>
                                </div>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 md:p-5">
                                    <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-1 md:mb-2">Recall</div>
                                    <div class="text-2xl md:text-4xl font-black text-purple-600">{{ diagnostics.evaluation.random_forest.recall.toFixed(2) }}</div>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-xl p-4 md:p-6 shadow-sm overflow-x-auto">
                                <div class="text-sm md:text-base font-bold text-gray-700 mb-3 md:mb-4">Confusion Matrix</div>
                                <div class="grid grid-cols-3 gap-2 md:gap-3 text-center text-[10px] md:text-sm items-center min-w-[250px]">
                                    <div class="col-start-2 text-[10px] md:text-xs uppercase text-gray-500 font-black leading-tight">Pred:<br class="block sm:hidden">Operational</div>
                                    <div class="col-start-3 text-[10px] md:text-xs uppercase text-gray-500 font-black leading-tight">Pred:<br class="block sm:hidden">Cancelled</div>
                                    
                                    <div class="text-[10px] md:text-xs uppercase text-gray-500 font-black text-right pr-2 leading-tight">Act:<br class="block sm:hidden">Operational</div>
                                    <div class="bg-emerald-100 border border-emerald-200 rounded p-2 md:p-4 text-emerald-700 font-mono text-base md:text-xl font-bold">{{ diagnostics.evaluation.random_forest.confusion_matrix[0].predicted_operational }}</div>
                                    <div class="bg-rose-50 border border-rose-200 rounded p-2 md:p-4 text-rose-600 font-mono text-base md:text-xl">{{ diagnostics.evaluation.random_forest.confusion_matrix[0].predicted_cancelled }}</div>
                                    
                                    <div class="text-[10px] md:text-xs uppercase text-gray-500 font-black text-right pr-2 leading-tight">Act:<br class="block sm:hidden">Cancelled</div>
                                    <div class="bg-rose-50 border border-rose-200 rounded p-2 md:p-4 text-rose-600 font-mono text-base md:text-xl">{{ diagnostics.evaluation.random_forest.confusion_matrix[1].predicted_operational }}</div>
                                    <div class="bg-gray-100 border border-gray-300 rounded p-2 md:p-4 text-gray-700 font-mono text-base md:text-xl font-bold">{{ diagnostics.evaluation.random_forest.confusion_matrix[1].predicted_cancelled }}</div>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-xl p-4 md:p-6 shadow-sm mt-6 md:mt-8">
                                <div class="text-sm md:text-base font-bold text-gray-700 mb-3 md:mb-4">Historical Accuracy Tracking</div>
                                <div class="h-40 md:h-56 relative w-full">
                                    <Line :data="rfChartData" :options="chartOptions" />
                                </div>
                            </div>
                        </div>

                        <!-- TOPSIS Evaluation -->
                        <div>
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 md:mb-5 border-b border-gray-100 pb-3 gap-2">
                                <h4 class="text-indigo-600 font-bold text-sm md:text-base uppercase tracking-wider">TOPSIS (Recommendation)</h4>
                                <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-3 py-1 rounded-full text-[10px] md:text-xs font-black uppercase shadow-sm self-start sm:self-auto">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Performance: Optimal
                                </div>
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 md:p-8 h-full flex flex-col justify-center space-y-6 md:space-y-8">
                                <div>
                                    <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-2 md:mb-3">Decision Criteria</div>
                                    <div class="flex flex-wrap gap-2 md:gap-3">
                                        <span v-for="criterion in diagnostics.evaluation.topsis.criteria" :key="criterion" class="bg-indigo-100 text-indigo-700 px-2 py-1 md:px-4 md:py-2 rounded md:rounded-lg text-xs md:text-base font-bold border border-indigo-200 shadow-sm">
                                            {{ criterion }}
                                        </span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                                    <div>
                                        <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-1 md:mb-2">Matrix Type</div>
                                        <div class="text-sm md:text-base font-bold text-gray-800">{{ diagnostics.evaluation.topsis.type }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-1 md:mb-2">Normalization</div>
                                        <div class="text-xs md:text-base font-bold text-gray-800 font-mono bg-white border border-gray-200 px-2 py-1 rounded inline-block">{{ diagnostics.evaluation.topsis.normalization_method }}</div>
                                    </div>
                                </div>
                                
                                <div class="border-t border-gray-200 pt-4 md:pt-6">
                                    <h5 class="text-[10px] md:text-sm font-bold text-gray-600 uppercase tracking-wider mb-3 md:mb-4">Ranking Accuracy Metrics</h5>
                                    <div class="grid grid-cols-2 gap-3 md:gap-4">
                                        <div class="bg-white border border-gray-200 rounded-xl p-3 md:p-4 shadow-sm">
                                            <div class="text-[10px] md:text-xs text-gray-500 font-bold uppercase">Spearman Corr</div>
                                            <div class="text-xl md:text-2xl font-black text-emerald-600 mt-1 md:mt-2 flex flex-col xl:flex-row xl:items-end gap-1">
                                                {{ diagnostics.evaluation.topsis.spearman_correlation.toFixed(2) }}
                                                <span class="text-[10px] md:text-xs text-emerald-500 flex items-center bg-emerald-50 px-1 py-0.5 rounded border border-emerald-100 w-fit">
                                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                                    {{ topsisTrend }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="bg-white border border-gray-200 rounded-xl p-3 md:p-4 shadow-sm">
                                            <div class="text-[10px] md:text-xs text-gray-500 font-bold uppercase">Consistency Ratio</div>
                                            <div class="text-xl md:text-2xl font-black text-blue-600 mt-1 md:mt-2">{{ diagnostics.evaluation.topsis.consistency_ratio.toFixed(3) }}</div>
                                        </div>
                                        <div class="bg-white border border-gray-200 rounded-xl p-3 md:p-5 col-span-2 flex flex-col sm:flex-row justify-between sm:items-center shadow-sm gap-2">
                                            <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase">Mean Closeness (Satisfaction)</div>
                                            <div class="text-2xl md:text-3xl font-black text-purple-600">{{ (diagnostics.evaluation.topsis.mean_closeness_coefficient * 100).toFixed(1) }}%</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-t border-gray-200 pt-4 md:pt-6">
                                    <h5 class="text-[10px] md:text-sm font-bold text-gray-600 uppercase tracking-wider mb-3 md:mb-4">Historical Ranking Correlation</h5>
                                    <div class="bg-white border border-gray-200 rounded-xl p-3 md:p-5 h-40 md:h-56 relative shadow-sm">
                                        <Line :data="topsisChartData" :options="chartOptions" />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Algorithmic Execution Pipeline -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden animate-fade-in-down" style="animation-delay: 0.2s">
                    <div class="p-4 md:p-6 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-lg md:text-2xl font-bold text-gray-900 flex items-center gap-2 md:gap-3">
                            <svg class="w-6 h-6 md:w-8 md:h-8 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            2. Algorithmic Execution Pipeline (Visualized)
                        </h3>
                    </div>
                    <div class="p-4 md:p-10">
                        
                        <!-- Visual Pipeline Stepper -->
                        <div class="relative w-full max-w-5xl mx-auto mb-10 md:mb-16 mt-2 md:mt-4">
                            <!-- Background connecting line -->
                            <div class="hidden lg:block absolute top-1/2 left-0 w-full h-1.5 bg-gray-100 -z-0 transform -translate-y-1/2 rounded-full"></div>
                            
                            <div class="flex flex-col lg:flex-row justify-between items-center relative z-10 gap-4 lg:gap-0">
                                <div v-for="(exec, index) in diagnostics.execution_pipeline" :key="exec.stage" class="bg-white p-4 md:p-5 rounded-xl md:rounded-2xl shadow-sm md:shadow-md border border-gray-200 flex flex-col items-center w-full lg:w-48 transform transition hover:-translate-y-1 hover:shadow-lg">
                                    <!-- Step Number -->
                                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full text-cyan-700 font-black text-sm md:text-lg flex items-center justify-center mb-2 md:mb-4 border-2 md:border-4 shadow-sm"
                                         :class="exec.status.includes('Optimal') ? 'bg-cyan-50 border-cyan-200' : 'bg-yellow-50 border-yellow-200'">
                                        {{ index + 1 }}
                                    </div>
                                    <!-- Title -->
                                    <h5 class="font-black text-gray-800 text-sm md:text-base text-center mb-1 md:mb-2">{{ exec.stage }}</h5>
                                    <!-- Algorithm -->
                                    <div class="text-[10px] md:text-xs font-mono font-bold text-cyan-600 text-center bg-gray-50 px-2 md:px-3 py-1.5 md:py-2 rounded-lg w-full mb-3 md:mb-4 border border-gray-100">
                                        {{ exec.algorithm }}
                                    </div>
                                    <!-- Time & Status -->
                                    <div class="flex justify-between w-full items-center">
                                        <span class="text-gray-500 font-mono font-bold text-[10px] md:text-xs bg-gray-50 px-1.5 md:px-2 py-1 rounded">{{ exec.time_ms }} ms</span>
                                        <span class="font-bold uppercase text-[10px] md:text-xs px-1.5 md:px-2 py-1 rounded flex items-center gap-1 border" :class="exec.status.includes('Optimal') ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : 'text-yellow-600 bg-yellow-50 border-yellow-200'">
                                            <svg v-if="exec.status.includes('Optimal')" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            {{ exec.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Random Forest Branches Tree Visualization -->
                        <div class="mt-8 md:mt-12 bg-gray-50 rounded-xl md:rounded-2xl p-4 md:p-8 border border-gray-200">
                            <h4 class="text-cyan-600 font-black mb-6 md:mb-8 text-sm md:text-lg uppercase tracking-wider text-center">Random Forest Decision Tree (Node Visualization)</h4>
                            
                            <div class="max-h-[500px] md:max-h-[600px] overflow-y-auto custom-scrollbar-light pr-2 md:pr-4 pb-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-6 md:gap-x-8 gap-y-10 md:gap-y-12 relative pt-6">
                                    
                                    <div v-for="branch in diagnostics.rf_branches" :key="branch.branch_id" class="bg-white border-2 border-gray-200 rounded-xl md:rounded-2xl p-4 md:p-6 shadow-sm md:shadow-md relative transform transition hover:scale-105 hover:border-cyan-400 z-10">
                                        
                                        <!-- Visual Top Connector -->
                                        <div class="absolute -top-6 left-1/2 w-0.5 h-6 bg-cyan-200 transform -translate-x-1/2"></div>
                                        <div class="absolute -top-8 left-1/2 w-3 h-3 md:w-4 md:h-4 rounded-full bg-cyan-100 border-2 border-cyan-400 transform -translate-x-1/2"></div>

                                        <div class="flex justify-between items-center mb-4 md:mb-5">
                                            <span class="text-xs md:text-sm font-black text-cyan-600 font-mono bg-cyan-50 px-2 py-1 rounded">{{ branch.branch_id }}</span>
                                            <span class="text-[10px] md:text-xs uppercase font-black px-2 py-1 md:px-3 md:py-1.5 rounded-full tracking-wider border"
                                                  :class="branch.prediction === 'Operational' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                                                {{ branch.prediction }}
                                            </span>
                                        </div>
                                        
                                        <div class="text-sm md:text-base text-gray-800 font-mono font-bold mb-4 md:mb-5 bg-gray-50 p-3 md:p-4 rounded-lg md:rounded-xl border border-gray-200 break-words shadow-inner">
                                            <span class="text-cyan-500 font-sans font-black text-xs md:text-sm mr-2">IF</span>{{ branch.rule }}
                                        </div>
                                        
                                        <!-- Test Results -->
                                        <div class="bg-gray-50 border border-gray-200 rounded-lg md:rounded-xl p-3 md:p-4 mb-3 md:mb-4">
                                            <div class="flex justify-between text-[10px] md:text-xs text-gray-500 font-bold mb-1 md:mb-2">
                                                <span class="uppercase">Node Accuracy</span>
                                                <span class="font-black text-emerald-600 text-xs md:text-sm">{{ branch.accuracy }}</span>
                                            </div>
                                            <div class="flex justify-between text-[10px] md:text-xs text-gray-500 font-bold">
                                                <span class="uppercase">Node Purity</span>
                                                <span class="font-black text-blue-600 text-xs md:text-sm">{{ branch.purity }}</span>
                                            </div>
                                        </div>

                                        <div class="flex justify-between items-center text-xs md:text-sm text-gray-500 font-bold border-t border-gray-100 pt-3 md:pt-4">
                                            <span>Gini: <span class="font-black text-gray-800">{{ branch.gini }}</span></span>
                                            <span>Samples: <span class="font-black text-gray-800">{{ branch.samples.toLocaleString() }}</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Hyperparameter Matrix & Math Foundations -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 md:gap-8">
                    
                    <!-- Hyperparameter Matrix -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden animate-fade-in-down" style="animation-delay: 0.3s">
                        <div class="p-4 md:p-6 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-lg md:text-2xl font-bold text-gray-900 flex items-center gap-2 md:gap-3">
                                <svg class="w-6 h-6 md:w-8 md:h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                3. Hyperparameter Configuration
                            </h3>
                        </div>
                        <div class="p-4 md:p-8 space-y-8 md:space-y-10">
                            <!-- RF Hyperparameters -->
                            <div>
                                <h4 class="text-emerald-600 font-bold mb-4 md:mb-5 text-sm md:text-base uppercase tracking-wider">Random Forest Configuration</h4>
                                <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                                    <table class="w-full text-xs md:text-base text-left">
                                        <tbody class="divide-y divide-gray-200">
                                            <tr v-for="(value, key) in diagnostics.hyperparameters.random_forest" :key="key" class="hover:bg-gray-100">
                                                <td class="px-3 py-2 md:px-5 md:py-4 font-mono font-bold text-gray-700 break-all">{{ key }}</td>
                                                <td class="px-3 py-2 md:px-5 md:py-4 font-mono text-emerald-600 text-right font-black text-sm md:text-lg">{{ value }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TOPSIS Hyperparameters -->
                            <div>
                                <h4 class="text-emerald-600 font-bold mb-4 md:mb-5 text-sm md:text-base uppercase tracking-wider">TOPSIS Preference Weights</h4>
                                <div class="space-y-3 md:space-y-5">
                                    <div v-for="(weights, pref) in diagnostics.hyperparameters.topsis_weights" :key="pref" class="bg-white rounded-xl border-2 border-gray-100 p-3 md:p-5 shadow-sm hover:border-emerald-200 transition">
                                        <div class="text-xs md:text-sm text-emerald-700 uppercase tracking-widest font-black mb-3 md:mb-4 border-b-2 border-gray-100 pb-2 md:pb-3">{{ pref }}</div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 text-center">
                                            <div v-for="(w, c) in weights" :key="c" class="bg-gray-50 sm:bg-transparent rounded p-2 sm:p-0">
                                                <div class="text-[10px] md:text-xs text-gray-500 font-bold uppercase">{{ c }}</div>
                                                <div class="font-mono text-gray-900 font-black text-base md:text-lg mt-1 md:mt-2">{{ w.toFixed(2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mathematical Foundations -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden animate-fade-in-down" style="animation-delay: 0.4s">
                        <div class="p-4 md:p-6 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-lg md:text-2xl font-bold text-gray-900 flex items-center gap-2 md:gap-3">
                                <svg class="w-6 h-6 md:w-8 md:h-8 text-fuchsia-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                4. Mathematical Foundations
                            </h3>
                        </div>
                        <div class="p-4 md:p-8 space-y-6 md:space-y-10">
                            <!-- Random Forest Math -->
                            <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 md:p-8 shadow-sm">
                                <h4 class="text-fuchsia-600 font-bold mb-4 md:mb-6 text-sm md:text-base uppercase tracking-wider">Random Forest Splitting Criteria</h4>
                                
                                <div class="mb-6 md:mb-8">
                                    <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-2 md:mb-3">Gini Impurity Formula</div>
                                    <div class="bg-white border-2 border-gray-200 rounded-xl p-4 md:p-6 flex justify-center items-center font-serif text-lg md:text-3xl text-gray-800 tracking-wider shadow-inner overflow-x-auto">
                                        <div class="flex items-center gap-2 md:gap-3 min-w-max">
                                            <i>Gini</i> = 1 - 
                                            <div class="flex flex-col justify-center items-center relative mx-1 md:mx-2">
                                                <span class="text-[8px] md:text-xs leading-none text-gray-400 font-sans"><i>c</i></span>
                                                <span class="text-3xl md:text-5xl leading-none">&sum;</span>
                                                <span class="text-[8px] md:text-xs leading-none text-gray-400 font-sans"><i>i=1</i></span>
                                            </div>
                                            (<i>p<sub>i</sub></i>)<sup>2</sup>
                                        </div>
                                    </div>
                                    <p class="text-[10px] md:text-xs text-gray-500 mt-2 md:mt-3 text-center font-bold">Where <i>c</i> is total number of classes, and <i>p<sub>i</sub></i> is probability/proportion of class <i>i</i>.</p>
                                </div>

                                <div>
                                    <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-2 md:mb-3">Information Entropy Formula</div>
                                    <div class="bg-white border-2 border-gray-200 rounded-xl p-4 md:p-6 flex justify-center items-center font-serif text-lg md:text-3xl text-gray-800 tracking-wider shadow-inner overflow-x-auto">
                                        <div class="flex items-center gap-2 md:gap-3 min-w-max">
                                            <i>Entropy</i> = - 
                                            <div class="flex flex-col justify-center items-center relative mx-1 md:mx-2">
                                                <span class="text-[8px] md:text-xs leading-none text-gray-400 font-sans"><i>c</i></span>
                                                <span class="text-3xl md:text-5xl leading-none">&sum;</span>
                                                <span class="text-[8px] md:text-xs leading-none text-gray-400 font-sans"><i>i=1</i></span>
                                            </div>
                                            <i>p<sub>i</sub></i> log<sub>2</sub>(<i>p<sub>i</sub></i>)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TOPSIS Math -->
                            <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 md:p-8 shadow-sm">
                                <h4 class="text-fuchsia-600 font-bold mb-4 md:mb-6 text-sm md:text-base uppercase tracking-wider">TOPSIS Ranking Distance</h4>
                                
                                <div class="mb-6 md:mb-8">
                                    <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-2 md:mb-3">Euclidean Distance to Ideal Best</div>
                                    <div class="bg-white border-2 border-gray-200 rounded-xl p-4 md:p-6 flex justify-center items-center font-serif text-lg md:text-3xl text-gray-800 tracking-wider shadow-inner overflow-x-auto">
                                        <div class="flex items-center gap-2 md:gap-3 min-w-max">
                                            <i>D<sub>i</sub><sup>*</sup></i> = 
                                            <span class="text-3xl md:text-5xl font-light">&radic;</span>
                                            <div class="border-t border-gray-400 md:border-t-2 pt-1 md:pt-2 -ml-1 md:-ml-2 flex items-center gap-1 md:gap-3"> 
                                                <div class="flex flex-col justify-center items-center relative mx-1 md:mx-2">
                                                    <span class="text-[8px] md:text-xs leading-none text-gray-400 font-sans"><i>n</i></span>
                                                    <span class="text-2xl md:text-4xl leading-none">&sum;</span>
                                                    <span class="text-[8px] md:text-xs leading-none text-gray-400 font-sans"><i>j=1</i></span>
                                                </div>
                                                (<i>v<sub>ij</sub></i> - <i>v<sub>j</sub><sup>*</sup></i>)<sup>2</sup> 
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[10px] md:text-xs text-gray-500 mt-2 md:mt-3 text-center font-bold">Where <i>v<sub>ij</sub></i> is weighted normalized value, and <i>v<sub>j</sub><sup>*</sup></i> is ideal best value.</p>
                                </div>

                                <div>
                                    <div class="text-[10px] md:text-sm text-gray-500 font-bold uppercase tracking-wider mb-2 md:mb-3">Performance Score (Ranking)</div>
                                    <div class="bg-white border-2 border-gray-200 rounded-xl p-4 md:p-6 flex justify-center items-center font-serif text-lg md:text-3xl text-gray-800 tracking-wider shadow-inner overflow-x-auto">
                                        <div class="flex items-center gap-2 md:gap-3 min-w-max">
                                            <i>S<sub>i</sub></i> = 
                                            <div class="inline-flex flex-col items-center">
                                                <span class="border-b border-gray-400 md:border-b-2 px-2 md:px-3 pb-1 md:pb-2"><i>D<sub>i</sub><sup>-</sup></i></span>
                                                <span class="pt-1 md:pt-2"><i>D<sub>i</sub><sup>*</sup></i> + <i>D<sub>i</sub><sup>-</sup></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const props = defineProps({
    diagnostics: Object
})

const rfTrend = computed(() => {
    const hist = props.diagnostics.evaluation.random_forest.historical_accuracy;
    const diff = (hist[hist.length - 1] - hist[hist.length - 2]) * 100;
    return diff > 0 ? `+${diff.toFixed(1)}%` : `${diff.toFixed(1)}%`;
});

const topsisTrend = computed(() => {
    const hist = props.diagnostics.evaluation.topsis.historical_correlation;
    const diff = (hist[hist.length - 1] - hist[hist.length - 2]) * 100;
    return diff > 0 ? `+${diff.toFixed(1)}%` : `${diff.toFixed(1)}%`;
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleFont: { size: 14, family: 'Inter' },
            bodyFont: { size: 14, family: 'Inter', weight: 'bold' },
            padding: 12,
            callbacks: {
                label: function(context) {
                    return context.parsed.y.toFixed(2);
                }
            }
        }
    },
    scales: {
        y: { 
            min: 0.7, 
            max: 1.0,
            ticks: { font: { size: 12, weight: 'bold' }, color: '#6B7280' },
            grid: { color: '#F3F4F6' }
        },
        x: {
            ticks: { font: { size: 12, weight: 'bold' }, color: '#6B7280' },
            grid: { display: false }
        }
    }
}

const rfChartData = computed(() => ({
    labels: ['Epoch 1', 'Epoch 2', 'Epoch 3', 'Epoch 4', 'Epoch 5', 'Latest'],
    datasets: [{
        label: 'Accuracy',
        data: props.diagnostics.evaluation.random_forest.historical_accuracy,
        borderColor: '#059669', // emerald-600
        backgroundColor: 'rgba(5, 150, 105, 0.15)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#059669',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 5,
        borderWidth: 3
    }]
}))

const topsisChartData = computed(() => ({
    labels: ['Epoch 1', 'Epoch 2', 'Epoch 3', 'Epoch 4', 'Epoch 5', 'Latest'],
    datasets: [{
        label: 'Correlation',
        data: props.diagnostics.evaluation.topsis.historical_correlation,
        borderColor: '#4f46e5', // indigo-600
        backgroundColor: 'rgba(79, 70, 229, 0.15)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#4f46e5',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 5,
        borderWidth: 3
    }]
}))
</script>

<style>
@keyframes fadeInDown {
    from { opacity: 0; transform: translate3d(0, -20px, 0); }
    to { opacity: 1; transform: translate3d(0, 0, 0); }
}
.animate-fade-in-down { animation: fadeInDown 0.6s ease-out both; }

.custom-scrollbar-light::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar-light::-webkit-scrollbar-track {
    background: rgba(243, 244, 246, 1); /* gray-100 */
    border-radius: 8px;
}
.custom-scrollbar-light::-webkit-scrollbar-thumb {
    background: rgba(6, 182, 212, 0.4); /* cyan-500 */
    border-radius: 8px;
}
.custom-scrollbar-light::-webkit-scrollbar-thumb:hover {
    background: rgba(6, 182, 212, 0.8); 
}
</style>
